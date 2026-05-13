<?php
/**
 * MCP Abilities — SP-2 read-only + SP-3 write tool registry
 *
 * Registers and executes MCP tools available to authenticated clients.
 *
 * Read-only tools (scope: read_only):
 *   g2rd/get-site-info  — Public site metadata (name, URL, WP version…)
 *   g2rd/list-posts     — Paginated list of published posts
 *   g2rd/get-post       — Single post by ID (published only)
 *
 * Write tools (scope: editor — require admin email confirmation before execution):
 *   g2rd/create-post    — Creates a new WordPress post (draft/pending/publish)
 *   g2rd/update-post    — Updates title/content/excerpt of an existing post
 *
 * All tools follow the MCP tool-result format:
 *   { content: [ { type: 'text', text: '<JSON string>' } ], isError: bool }
 *
 * @package    G2RD
 * @since      1.12.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * MCP tool registry and executor (read-only + write with confirmation queue).
 */
class McpAbilities {

	/** @var array<string, array<string, mixed>> Registered tool definitions. */
	private array $registry;

	/** @var McpConfirmationQueue|null Write-ability confirmation queue. */
	private ?McpConfirmationQueue $queue;

	/**
	 * Registers all tool definitions and wires in the confirmation queue.
	 *
	 * @param McpConfirmationQueue|null $queue Confirmation queue (null = write tools return error).
	 */
	public function __construct( ?McpConfirmationQueue $queue = null ) {
		$this->queue    = $queue;
		$this->registry = [
			// ── Read-only tools ────────────────────────────────────────────────
			'g2rd/get-site-info' => [
				'name'           => 'g2rd/get-site-info',
				'description'    => 'Returns public WordPress site metadata: name, description, URL, language, WP version and timezone.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(), // empty object — no arguments
				],
			],
			'g2rd/list-posts'    => [
				'name'           => 'g2rd/list-posts',
				'description'    => 'Returns a paginated list of published posts. Each item includes ID, title, slug, date, excerpt and URL.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'post_type' => [
							'type'        => 'string',
							'description' => 'Post type slug (default: post).',
							'default'     => 'post',
						],
						'per_page'  => [
							'type'        => 'integer',
							'description' => 'Results per page — 1 to 50 (default: 10).',
							'minimum'     => 1,
							'maximum'     => 50,
							'default'     => 10,
						],
						'page'      => [
							'type'        => 'integer',
							'description' => '1-based page number (default: 1).',
							'minimum'     => 1,
							'default'     => 1,
						],
					],
				],
			],
			'g2rd/get-post'      => [
				'name'           => 'g2rd/get-post',
				'description'    => 'Returns a single published post by ID. Includes title, plain-text content, excerpt, status, author and dates.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'post_id' => [
							'type'        => 'integer',
							'description' => 'WordPress post ID.',
							'minimum'     => 1,
						],
					],
					'required'   => [ 'post_id' ],
				],
			],
			// ── Write tools (editor scope — confirmation required) ─────────────
			'g2rd/create-post'   => [
				'name'           => 'g2rd/create-post',
				'description'    => 'Creates a new WordPress post. Requires administrator email confirmation before execution. Returns a pending status with expiry.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'title'     => [
							'type'        => 'string',
							'description' => 'Post title (required).',
						],
						'content'   => [
							'type'        => 'string',
							'description' => 'Post content (HTML allowed).',
							'default'     => '',
						],
						'excerpt'   => [
							'type'        => 'string',
							'description' => 'Post excerpt.',
							'default'     => '',
						],
						'status'    => [
							'type'        => 'string',
							'description' => 'Post status: draft, pending or publish (default: draft).',
							'default'     => 'draft',
						],
						'post_type' => [
							'type'        => 'string',
							'description' => 'Post type slug (default: post).',
							'default'     => 'post',
						],
					],
					'required'   => [ 'title' ],
				],
			],
			'g2rd/update-post'   => [
				'name'           => 'g2rd/update-post',
				'description'    => 'Updates an existing WordPress post. Requires administrator email confirmation before execution. Returns a pending status with expiry.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'post_id' => [
							'type'        => 'integer',
							'description' => 'WordPress post ID (required).',
							'minimum'     => 1,
						],
						'title'   => [
							'type'        => 'string',
							'description' => 'New post title.',
						],
						'content' => [
							'type'        => 'string',
							'description' => 'New post content (HTML allowed).',
						],
						'excerpt' => [
							'type'        => 'string',
							'description' => 'New post excerpt.',
						],
					],
					'required'   => [ 'post_id' ],
				],
			],
		];
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Returns the tool list in MCP tools/list format.
	 *
	 * Strips internal fields (required_scope, wp_capability) before returning.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function list_tools(): array {
		return \array_values(
			\array_map(
				static fn( array $tool ): array => [
					'name'        => $tool['name'],
					'description' => $tool['description'],
					'inputSchema' => $tool['inputSchema'],
				],
				$this->registry
			)
		);
	}

	/**
	 * Returns a tool definition by name, or null if not registered.
	 *
	 * @param string $name Tool name (e.g. 'g2rd/get-post').
	 * @return array<string, mixed>|null
	 */
	public function get( string $name ): ?array {
		return $this->registry[ $name ] ?? null;
	}

	/**
	 * Executes a registered tool and returns an MCP tool-result payload.
	 *
	 * Write tools enqueue the operation and return a pending acknowledgement;
	 * execution happens only after the administrator confirms via email link.
	 *
	 * @param string               $name        Tool name.
	 * @param mixed                $arguments   Arguments from tools/call params.
	 * @param array<string, mixed> $gate_result Authorized gate result (user_id, token_id, scope, client_ip…).
	 * @return array<string, mixed> MCP tool result.
	 */
	public function call( string $name, mixed $arguments, array $gate_result ): array {
		$args = \is_array( $arguments ) ? $arguments : [];

		switch ( $name ) {
			case 'g2rd/get-site-info':
				return $this->exec_get_site_info();
			case 'g2rd/list-posts':
				return $this->exec_list_posts( $args );
			case 'g2rd/get-post':
				return $this->exec_get_post( $args );
			case 'g2rd/create-post':
				return $this->exec_enqueue_write( 'g2rd/create-post', $args, $gate_result );
			case 'g2rd/update-post':
				return $this->exec_enqueue_write( 'g2rd/update-post', $args, $gate_result );
			default:
				return $this->tool_error( "Unknown tool: {$name}" );
		}
	}

	// ── Read-only tool implementations ────────────────────────────────────────

	/**
	 * Returns public site metadata (tool: g2rd/get-site-info).
	 *
	 * @return array<string, mixed>
	 */
	private function exec_get_site_info(): array {
		global $wp_version;

		$data = [
			'name'        => \get_bloginfo( 'name' ),
			'description' => \get_bloginfo( 'description' ),
			'url'         => \get_bloginfo( 'url' ),
			'language'    => \get_bloginfo( 'language' ),
			'wp_version'  => (string) $wp_version,
			'timezone'    => \wp_timezone_string(),
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns a paginated list of published posts (tool: g2rd/list-posts).
	 *
	 * Only returns post types that are public or publicly queryable.
	 *
	 * @param array<string, mixed> $args Tool arguments.
	 * @return array<string, mixed>
	 */
	private function exec_list_posts( array $args ): array {
		$post_type = \sanitize_key( (string) ( $args['post_type'] ?? 'post' ) );
		$per_page  = \min( 50, \max( 1, \absint( $args['per_page'] ?? 10 ) ) );
		$page      = \max( 1, \absint( $args['page'] ?? 1 ) );

		$pto = \get_post_type_object( $post_type );
		if ( null === $pto || ( ! $pto->publicly_queryable && ! $pto->public ) ) {
			return $this->tool_error( "Post type not accessible: {$post_type}" );
		}

		$query = new \WP_Query( [
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
		] );

		$posts = [];
		foreach ( $query->posts as $post ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				continue;
			}
			$posts[] = [
				'id'      => $post->ID,
				'title'   => \get_the_title( $post ),
				'slug'    => $post->post_name,
				'date'    => $post->post_date_gmt,
				'excerpt' => \get_the_excerpt( $post ),
				'url'     => \get_permalink( $post ),
			];
		}

		$data = [
			'posts'       => $posts,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns a single published post by ID (tool: g2rd/get-post).
	 *
	 * Only `publish` status posts are returned for read_only scope clients.
	 *
	 * @param array<string, mixed> $args Tool arguments.
	 * @return array<string, mixed>
	 */
	private function exec_get_post( array $args ): array {
		$post_id = \absint( $args['post_id'] ?? 0 );

		if ( $post_id <= 0 ) {
			return $this->tool_error( 'Missing required argument: post_id' );
		}

		$post = \get_post( $post_id );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return $this->tool_error( "Post not found: {$post_id}" );
		}

		if ( 'publish' !== $post->post_status ) {
			return $this->tool_error( "Post not accessible: {$post_id}" );
		}

		$author = \get_userdata( (int) $post->post_author );

		$data = [
			'id'          => $post->ID,
			'title'       => \get_the_title( $post ),
			'slug'        => $post->post_name,
			'status'      => $post->post_status,
			'date'        => $post->post_date_gmt,
			'modified'    => $post->post_modified_gmt,
			'content'     => \wp_strip_all_tags( $post->post_content ),
			'excerpt'     => \get_the_excerpt( $post ),
			'url'         => \get_permalink( $post ),
			'post_type'   => $post->post_type,
			'author_name' => $author ? (string) $author->display_name : '',
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	// ── Write tool implementations ────────────────────────────────────────────

	/**
	 * Enqueues a write tool for human confirmation (tools: g2rd/create-post, g2rd/update-post).
	 *
	 * Returns a pending acknowledgement rather than executing the operation.
	 * The actual write runs in McpConfirmationQueue::confirm() after admin approval.
	 *
	 * @param string               $ability_name Registered tool name.
	 * @param array<string, mixed> $args         Tool arguments.
	 * @param array<string, mixed> $gate_result  Gate result containing user_id, token_id, scope, client_ip.
	 * @return array<string, mixed>
	 */
	private function exec_enqueue_write( string $ability_name, array $args, array $gate_result ): array {
		if ( 'editor' !== ( $gate_result['scope'] ?? '' ) ) {
			return $this->tool_error( 'Write tools require editor scope.' );
		}

		if ( null === $this->queue ) {
			return $this->tool_error( 'Confirmation queue unavailable.' );
		}

		$result = $this->queue->enqueue(
			(int) ( $gate_result['user_id'] ?? 0 ),
			(int) ( $gate_result['token_id'] ?? 0 ),
			(string) ( $gate_result['client_ip'] ?? '0.0.0.0' ),
			$ability_name,
			$args
		);

		if ( false === $result ) {
			return $this->tool_error( 'Failed to enqueue operation. Please retry.' );
		}

		$data = [
			'status'     => 'pending',
			'message'    => 'An email has been sent to the administrator for confirmation. The operation will execute after approval.',
			'expires_at' => $result['expires_at'],
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	// ── Response helpers ──────────────────────────────────────────────────────

	/**
	 * Wraps a JSON string in an MCP tool success payload.
	 *
	 * @param string $json JSON-encoded result text.
	 * @return array<string, mixed>
	 */
	private function tool_success( string $json ): array {
		return [
			'content' => [
				[
					'type' => 'text',
					'text' => $json,
				],
			],
			'isError' => false,
		];
	}

	/**
	 * Wraps an error message in an MCP tool error payload.
	 *
	 * @param string $message Error description.
	 * @return array<string, mixed>
	 */
	private function tool_error( string $message ): array {
		return [
			'content' => [
				[
					'type' => 'text',
					'text' => $message,
				],
			],
			'isError' => true,
		];
	}
}
