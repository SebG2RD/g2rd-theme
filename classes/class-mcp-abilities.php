<?php
/**
 * MCP Abilities — WordPress control panel via MCP protocol
 *
 * Registers and executes MCP tools for authenticated Claude agents.
 * Scopes: read_only (all read tools) | editor (write tools, email confirmation required).
 *
 * Read tools:
 *   g2rd/get-site-info     — Public site metadata (name, URL, WP/PHP versions…)
 *   g2rd/list-posts        — Paginated post list with status, search, category, tag filters
 *   g2rd/get-post          — Single post with HTML content, taxonomy, featured image, SEO
 *   g2rd/get-post-meta     — All meta fields for a post
 *   g2rd/list-categories   — All categories with counts and hierarchy
 *   g2rd/list-tags         — All tags with counts
 *   g2rd/list-media        — Media library with pagination
 *   g2rd/get-media         — Single media attachment details
 *   g2rd/get-seo-data      — SEO meta for a post (Yoast / Rank Math / SEOPress / AIOSEO)
 *   g2rd/get-seo-overview  — Site-wide SEO audit (missing meta, noindex, no featured image)
 *   g2rd/get-redirections  — Active URL redirections (Redirection plugin / Rank Math)
 *   g2rd/list-plugins      — Installed plugins with active status and update availability
 *   g2rd/get-theme-info    — Active theme details
 *   g2rd/list-themes       — All installed themes
 *   g2rd/get-options       — Whitelisted WordPress site options
 *   g2rd/get-users         — User list (no passwords or sensitive data)
 *   g2rd/get-site-health   — Server and WordPress environment info
 *   g2rd/get-cron-jobs     — Scheduled WP-Cron tasks
 *   g2rd/list-menus        — Navigation menus with their items
 *
 * Write tools (editor scope — administrator email confirmation required):
 *   g2rd/create-post       — Create a new WordPress post
 *   g2rd/update-post       — Update post content, taxonomy, status, template
 *   g2rd/delete-post       — Move post to trash (never permanent delete)
 *   g2rd/update-post-meta  — Update a specific post meta field
 *   g2rd/update-seo-data   — Update SEO meta via the detected SEO plugin
 *   g2rd/create-redirection — Create a URL redirection (requires Redirection plugin)
 *   g2rd/create-category   — Create a new category
 *   g2rd/create-tag        — Create a new tag
 *   g2rd/update-media      — Update media alt text, title, description, caption
 *   g2rd/activate-plugin   — Activate an installed plugin
 *   g2rd/deactivate-plugin — Deactivate an active plugin (G2RD theme protected)
 *   g2rd/update-plugin     — Update a plugin to its latest available version
 *   g2rd/update-option     — Update a whitelisted WordPress option
 *   g2rd/flush-cache       — Purge all caches (WP Rocket, LiteSpeed, W3TC…)
 *   g2rd/update-menu-item  — Update a navigation menu item
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

			// ── Content read tools ─────────────────────────────────────────────
			'g2rd_get-site-info'     => [
				'name'           => 'g2rd_get-site-info',
				'description'    => 'Returns public WordPress site metadata: name, description, URL, language, WP version and timezone.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_list-posts'        => [
				'name'           => 'g2rd_list-posts',
				'description'    => 'Returns a paginated list of posts. With read_only scope only published posts are returned; editor scope unlocks non-publish statuses via the "status" parameter. Supports keyword search and category/tag filters.',
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
						'status'    => [
							'type'        => 'string',
							'description' => 'Post status filter (default: publish). Non-publish values require editor scope.',
							'default'     => 'publish',
							'enum'        => [ 'publish', 'draft', 'pending', 'private', 'future', 'trash', 'any' ],
						],
						'search'    => [
							'type'        => 'string',
							'description' => 'Keyword search in post title and content.',
						],
						'category'  => [
							'type'        => 'string',
							'description' => 'Filter by category slug.',
						],
						'tag'       => [
							'type'        => 'string',
							'description' => 'Filter by tag slug.',
						],
					],
				],
			],
			'g2rd_get-post'          => [
				'name'           => 'g2rd_get-post',
				'description'    => 'Returns a single post by ID with HTML content, plain-text content, categories, tags, featured image, template, SEO meta and author. With read_only scope only published posts are accessible; editor scope also returns draft, pending and private posts.',
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
			'g2rd_get-post-meta'     => [
				'name'           => 'g2rd_get-post-meta',
				'description'    => 'Returns all meta fields for a post. Published posts are accessible with read_only scope; non-published posts require editor scope.',
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

			// ── Taxonomy read tools ────────────────────────────────────────────
			'g2rd_list-categories'   => [
				'name'           => 'g2rd_list-categories',
				'description'    => 'Returns all categories with ID, name, slug, description, parent ID and post count.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_list-tags'         => [
				'name'           => 'g2rd_list-tags',
				'description'    => 'Returns all tags with ID, name, slug, description and post count.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],

			// ── Media read tools ───────────────────────────────────────────────
			'g2rd_list-media'        => [
				'name'           => 'g2rd_list-media',
				'description'    => 'Returns a paginated list of media library items with ID, URL, title, alt, mime type, dimensions and file size.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'per_page' => [
							'type'        => 'integer',
							'description' => 'Results per page — 1 to 50 (default: 20).',
							'minimum'     => 1,
							'maximum'     => 50,
							'default'     => 20,
						],
						'page'     => [
							'type'        => 'integer',
							'description' => '1-based page number (default: 1).',
							'minimum'     => 1,
							'default'     => 1,
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Keyword filter for media title.',
						],
					],
				],
			],
			'g2rd_get-media'         => [
				'name'           => 'g2rd_get-media',
				'description'    => 'Returns detailed information for a single media attachment.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'media_id' => [
							'type'        => 'integer',
							'description' => 'WordPress attachment ID.',
							'minimum'     => 1,
						],
					],
					'required'   => [ 'media_id' ],
				],
			],

			// ── SEO read tools ─────────────────────────────────────────────────
			'g2rd_get-seo-data'      => [
				'name'           => 'g2rd_get-seo-data',
				'description'    => 'Returns SEO meta for a post: title, description, canonical URL, noindex flag, Open Graph fields and focus keyword. Auto-detects active SEO plugin (Yoast, Rank Math, SEOPress, AIOSEO).',
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
			'g2rd_get-seo-overview'  => [
				'name'           => 'g2rd_get-seo-overview',
				'description'    => 'Returns a site-wide SEO audit: count of published posts missing meta title, meta description, featured image, and posts marked noindex. Checks up to 500 most-recent published posts.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_get-redirections'  => [
				'name'           => 'g2rd_get-redirections',
				'description'    => 'Returns active URL redirections. Compatible with Redirection plugin and Rank Math.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],

			// ── Admin read tools ───────────────────────────────────────────────
			'g2rd_list-plugins'      => [
				'name'           => 'g2rd_list-plugins',
				'description'    => 'Returns all installed plugins: name, version, active status, available update. Requires manage_options capability.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'manage_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_get-theme-info'    => [
				'name'           => 'g2rd_get-theme-info',
				'description'    => 'Returns active theme details: name, version, template, available update.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_list-themes'       => [
				'name'           => 'g2rd_list-themes',
				'description'    => 'Returns all installed themes: name, version, active status.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_get-options'       => [
				'name'           => 'g2rd_get-options',
				'description'    => 'Returns a whitelisted set of WordPress site options (blogname, blogdescription, timezone, posts_per_page, etc.). Never exposes passwords, API keys or security salts. Requires manage_options capability.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'manage_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_get-users'         => [
				'name'           => 'g2rd_get-users',
				'description'    => 'Returns a list of WordPress users: ID, display name, email, role and post count. Never exposes passwords or authentication tokens. Requires list_users capability.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'list_users',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'per_page' => [
							'type'        => 'integer',
							'description' => 'Results per page — 1 to 100 (default: 20).',
							'minimum'     => 1,
							'maximum'     => 100,
							'default'     => 20,
						],
						'page'     => [
							'type'        => 'integer',
							'description' => '1-based page number (default: 1).',
							'minimum'     => 1,
							'default'     => 1,
						],
					],
				],
			],

			// ── System read tools ──────────────────────────────────────────────
			'g2rd_get-site-health'   => [
				'name'           => 'g2rd_get-site-health',
				'description'    => 'Returns server and WordPress health info: PHP version, WP version, MySQL version, HTTPS status, memory limits, available updates counts, active plugins count, debug mode. Requires manage_options.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'manage_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_get-cron-jobs'     => [
				'name'           => 'g2rd_get-cron-jobs',
				'description'    => 'Returns all scheduled WP-Cron tasks: hook name, next run datetime (UTC) and recurrence interval.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'manage_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_list-menus'        => [
				'name'           => 'g2rd_list-menus',
				'description'    => 'Returns all navigation menus with their items (title, URL, parent, order, type).',
				'required_scope' => 'read_only',
				'wp_capability'  => 'read',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],

			// ── Write tools (editor scope — confirmation required) ─────────────
			'g2rd_create-post'       => [
				'name'           => 'g2rd_create-post',
				'description'    => 'Creates a new WordPress post. Requires administrator email confirmation before execution.',
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
							'enum'        => [ 'draft', 'pending', 'publish' ],
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
			'g2rd_update-post'       => [
				'name'           => 'g2rd_update-post',
				'description'    => 'Updates an existing WordPress post: title, content, excerpt, status, categories, tags, featured image, slug, publish date and page template. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'post_id'           => [
							'type'        => 'integer',
							'description' => 'WordPress post ID (required).',
							'minimum'     => 1,
						],
						'title'             => [
							'type'        => 'string',
							'description' => 'New post title.',
						],
						'content'           => [
							'type'        => 'string',
							'description' => 'New post content (HTML allowed).',
						],
						'excerpt'           => [
							'type'        => 'string',
							'description' => 'New post excerpt.',
						],
						'status'            => [
							'type'        => 'string',
							'description' => 'New post status.',
							'enum'        => [ 'draft', 'pending', 'publish', 'future', 'private' ],
						],
						'categories'        => [
							'type'        => 'array',
							'description' => 'Array of category IDs to assign (replaces existing).',
							'items'       => [ 'type' => 'integer' ],
						],
						'tags'              => [
							'type'        => 'array',
							'description' => 'Array of tag slugs or names (replaces existing).',
							'items'       => [ 'type' => 'string' ],
						],
						'featured_image_id' => [
							'type'        => 'integer',
							'description' => 'Attachment ID to set as featured image (0 to remove).',
						],
						'slug'              => [
							'type'        => 'string',
							'description' => 'New post slug (URL-safe).',
						],
						'date'              => [
							'type'        => 'string',
							'description' => 'Publish date in Y-m-d H:i:s format (UTC). Required when status is future.',
						],
						'template'          => [
							'type'        => 'string',
							'description' => 'Page template file slug (empty string for default).',
						],
					],
					'required'   => [ 'post_id' ],
				],
			],
			'g2rd_delete-post'       => [
				'name'           => 'g2rd_delete-post',
				'description'    => 'Moves a post to the trash. Never performs permanent deletion. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'delete_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'post_id' => [
							'type'        => 'integer',
							'description' => 'WordPress post ID (required).',
							'minimum'     => 1,
						],
					],
					'required'   => [ 'post_id' ],
				],
			],
			'g2rd_update-post-meta'  => [
				'name'           => 'g2rd_update-post-meta',
				'description'    => 'Updates a specific meta field for a post. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'post_id'    => [
							'type'        => 'integer',
							'description' => 'WordPress post ID (required).',
							'minimum'     => 1,
						],
						'meta_key'   => [
							'type'        => 'string',
							'description' => 'Meta key to update (required).',
						],
						'meta_value' => [
							'type'        => 'string',
							'description' => 'New meta value (required).',
						],
					],
					'required'   => [ 'post_id', 'meta_key', 'meta_value' ],
				],
			],
			'g2rd_update-seo-data'   => [
				'name'           => 'g2rd_update-seo-data',
				'description'    => 'Updates SEO meta for a post via the detected SEO plugin (Yoast, Rank Math, SEOPress, AIOSEO). Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'post_id'         => [
							'type'        => 'integer',
							'description' => 'WordPress post ID (required).',
							'minimum'     => 1,
						],
						'meta_title'      => [
							'type'        => 'string',
							'description' => 'SEO title tag.',
						],
						'meta_description' => [
							'type'        => 'string',
							'description' => 'SEO meta description.',
						],
						'canonical'       => [
							'type'        => 'string',
							'description' => 'Canonical URL.',
						],
						'noindex'         => [
							'type'        => 'boolean',
							'description' => 'Set to true to mark the page as noindex.',
						],
						'og_title'        => [
							'type'        => 'string',
							'description' => 'Open Graph title.',
						],
						'og_description'  => [
							'type'        => 'string',
							'description' => 'Open Graph description.',
						],
						'focus_keyword'   => [
							'type'        => 'string',
							'description' => 'Focus keyword or keyphrase.',
						],
					],
					'required'   => [ 'post_id' ],
				],
			],
			'g2rd_create-redirection' => [
				'name'           => 'g2rd_create-redirection',
				'description'    => 'Creates a URL redirection. Requires the Redirection plugin to be active. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'manage_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'source' => [
							'type'        => 'string',
							'description' => 'Source URL path (e.g. /old-page).',
						],
						'target' => [
							'type'        => 'string',
							'description' => 'Target URL or path.',
						],
						'type'   => [
							'type'        => 'integer',
							'description' => 'HTTP status code: 301 (permanent) or 302 (temporary). Default: 301.',
							'default'     => 301,
							'enum'        => [ 301, 302 ],
						],
					],
					'required'   => [ 'source', 'target' ],
				],
			],
			'g2rd_create-category'   => [
				'name'           => 'g2rd_create-category',
				'description'    => 'Creates a new post category. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'manage_categories',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'name'        => [
							'type'        => 'string',
							'description' => 'Category name (required).',
						],
						'slug'        => [
							'type'        => 'string',
							'description' => 'Category slug (auto-generated if omitted).',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'Category description.',
							'default'     => '',
						],
						'parent_id'   => [
							'type'        => 'integer',
							'description' => 'Parent category ID (0 = top-level).',
							'default'     => 0,
						],
					],
					'required'   => [ 'name' ],
				],
			],
			'g2rd_create-tag'        => [
				'name'           => 'g2rd_create-tag',
				'description'    => 'Creates a new post tag. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'manage_categories',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'name'        => [
							'type'        => 'string',
							'description' => 'Tag name (required).',
						],
						'slug'        => [
							'type'        => 'string',
							'description' => 'Tag slug (auto-generated if omitted).',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'Tag description.',
							'default'     => '',
						],
					],
					'required'   => [ 'name' ],
				],
			],
			'g2rd_update-media'      => [
				'name'           => 'g2rd_update-media',
				'description'    => 'Updates media alt text, title, description and caption. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'upload_files',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'media_id'    => [
							'type'        => 'integer',
							'description' => 'WordPress attachment ID (required).',
							'minimum'     => 1,
						],
						'alt'         => [
							'type'        => 'string',
							'description' => 'Alt text.',
						],
						'title'       => [
							'type'        => 'string',
							'description' => 'Media title.',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'Media description.',
						],
						'caption'     => [
							'type'        => 'string',
							'description' => 'Media caption.',
						],
					],
					'required'   => [ 'media_id' ],
				],
			],
			'g2rd_activate-plugin'   => [
				'name'           => 'g2rd_activate-plugin',
				'description'    => 'Activates an installed plugin by its plugin file path (e.g. "akismet/akismet.php"). Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'activate_plugins',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'plugin_file' => [
							'type'        => 'string',
							'description' => 'Plugin file path relative to the plugins directory (e.g. "akismet/akismet.php").',
						],
					],
					'required'   => [ 'plugin_file' ],
				],
			],
			'g2rd_deactivate-plugin' => [
				'name'           => 'g2rd_deactivate-plugin',
				'description'    => 'Deactivates an active plugin. The G2RD theme and its core MCP plugin cannot be deactivated. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'activate_plugins',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'plugin_file' => [
							'type'        => 'string',
							'description' => 'Plugin file path relative to the plugins directory.',
						],
					],
					'required'   => [ 'plugin_file' ],
				],
			],
			'g2rd_update-plugin'     => [
				'name'           => 'g2rd_update-plugin',
				'description'    => 'Updates a plugin to its latest available version. Logs the previous version before updating. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'update_plugins',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'plugin_file' => [
							'type'        => 'string',
							'description' => 'Plugin file path relative to the plugins directory.',
						],
					],
					'required'   => [ 'plugin_file' ],
				],
			],
			'g2rd_update-option'     => [
				'name'           => 'g2rd_update-option',
				'description'    => 'Updates a whitelisted WordPress option. Allowed keys: blogname, blogdescription, timezone_string, date_format, time_format, posts_per_page, default_comment_status, default_ping_status, permalink_structure. All other keys are refused. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'manage_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'option_key'   => [
							'type'        => 'string',
							'description' => 'Option key to update.',
							'enum'        => [
								'blogname',
								'blogdescription',
								'timezone_string',
								'date_format',
								'time_format',
								'posts_per_page',
								'default_comment_status',
								'default_ping_status',
								'permalink_structure',
							],
						],
						'option_value' => [
							'type'        => 'string',
							'description' => 'New option value.',
						],
					],
					'required'   => [ 'option_key', 'option_value' ],
				],
			],
			'g2rd_flush-cache'       => [
				'name'           => 'g2rd_flush-cache',
				'description'    => 'Purges all active caches: WP object cache, WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'manage_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_update-menu-item'  => [
				'name'           => 'g2rd_update-menu-item',
				'description'    => 'Updates a navigation menu item: title, URL, order and parent. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_theme_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'item_id' => [
							'type'        => 'integer',
							'description' => 'Menu item post ID (required).',
							'minimum'     => 1,
						],
						'title'   => [
							'type'        => 'string',
							'description' => 'New navigation label.',
						],
						'url'     => [
							'type'        => 'string',
							'description' => 'New URL.',
						],
						'order'   => [
							'type'        => 'integer',
							'description' => 'Menu item order (menu_order).',
						],
						'parent'  => [
							'type'        => 'integer',
							'description' => 'Parent menu item post ID (0 = top-level).',
						],
					],
					'required'   => [ 'item_id' ],
				],
			],

			// ── Post types & FluentCart products ──────────────────────────────────
			'g2rd_list-post-types'       => [
				'name'           => 'g2rd_list-post-types',
				'description'    => 'Lists every registered post type with its slug, label and whether g2rd_list-posts can read it. Call this before using a post type you are unsure about: g2rd_create-post refuses unknown types, and guessing produces errors.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => new \stdClass(),
				],
			],
			'g2rd_list-products'         => [
				'name'           => 'g2rd_list-products',
				'description'    => 'Lists FluentCart products with their ID, title, slug, status and public URL. Requires FluentCart to be active.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'per_page' => [
							'type'        => 'integer',
							'description' => 'Results per page (1-100, default 20).',
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'page'     => [
							'type'        => 'integer',
							'description' => 'Page number (default 1).',
							'minimum'     => 1,
						],
						'status'   => [
							'type'        => 'string',
							'description' => 'Post status filter, or "any" (default).',
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Free-text search on the product title.',
						],
					],
				],
			],
			'g2rd_get-product'           => [
				'name'           => 'g2rd_get-product',
				'description'    => 'Returns one FluentCart product with its pricing variations, default variation and an is_purchasable flag telling you whether the product has a usable price.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'product_id' => [
							'type'        => 'integer',
							'description' => 'Product post ID.',
							'minimum'     => 1,
						],
					],
					'required'   => [ 'product_id' ],
				],
			],
			'g2rd_create-product'        => [
				'name'           => 'g2rd_create-product',
				'description'    => 'Creates a complete, purchasable FluentCart product: the post, its product_details row and its pricing variations, in one atomic operation. Prices are given in CENTS (20000 = 200.00). Requires administrator email confirmation. Use this instead of g2rd_create-post for products.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'title'              => [
							'type'        => 'string',
							'description' => 'Product name (required).',
						],
						'slug'               => [
							'type'        => 'string',
							'description' => 'URL slug. Defaults to a sanitized title.',
						],
						'content'            => [
							'type'        => 'string',
							'description' => 'Long description, HTML allowed.',
						],
						'excerpt'            => [
							'type'        => 'string',
							'description' => 'Short description.',
						],
						'status'             => [
							'type'        => 'string',
							'description' => 'Post status.',
							'enum'        => McpProducts::STATUSES,
						],
						'fulfillment_type'   => [
							'type'        => 'string',
							'description' => 'How the product is delivered.',
							'enum'        => McpProducts::FULFILLMENT_TYPES,
						],
						'product_categories' => [
							'type'        => 'array',
							'description' => 'Category slugs or term IDs.',
							'items'       => [ 'type' => 'string' ],
						],
						'featured_image_id'  => [
							'type'        => 'integer',
							'description' => 'Attachment ID for the featured image.',
						],
						'gallery_image_ids'  => [
							'type'        => 'array',
							'description' => 'Attachment IDs for the product gallery.',
							'items'       => [ 'type' => 'integer' ],
						],
						'manage_stock'       => [
							'type'        => 'boolean',
							'description' => 'Enable stock management on the product.',
						],
						'variations'         => [
							'type'        => 'array',
							'description' => 'Pricing entries. At least one is required: a product without a priced variation is not purchasable.',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'label'                  => [
										'type'        => 'string',
										'description' => 'Variation name. Defaults to the product title.',
									],
									'payment_type'           => [
										'type'        => 'string',
										'description' => 'One-off payment or recurring subscription.',
										'enum'        => McpProducts::PAYMENT_TYPES,
									],
									'price'                  => [
										'type'        => 'integer',
										'description' => 'Price in CENTS as an integer. 20000 means 200.00. Decimal values are refused.',
										'minimum'     => 0,
									],
									'compare_at_price'       => [
										'type'        => 'integer',
										'description' => 'Struck-through reference price, in cents.',
										'minimum'     => 0,
									],
									'billing_interval'       => [
										'type'        => 'string',
										'description' => 'Subscription period unit.',
										'enum'        => McpProducts::BILLING_INTERVALS,
									],
									'billing_interval_count' => [
										'type'        => 'integer',
										'description' => 'Number of periods between charges (default 1).',
										'minimum'     => 1,
									],
									'trial_days'             => [
										'type'        => 'integer',
										'description' => 'Free trial length in days. 0 = none.',
										'minimum'     => 0,
									],
									'cycles'                 => [
										'type'        => 'integer',
										'description' => 'Number of billing cycles. Omit or 0 for unlimited renewal.',
										'minimum'     => 0,
									],
									'stock'                  => [
										'type'        => 'integer',
										'description' => 'Stock quantity. Omit entirely for unlimited stock.',
										'minimum'     => 0,
									],
									'is_default'             => [
										'type'        => 'boolean',
										'description' => 'Marks the default variation. Exactly one per product; the first is used if none is set.',
									],
								],
								'required'   => [ 'price' ],
							],
						],
					],
					'required'   => [ 'title', 'variations' ],
				],
			],
			'g2rd_update-product'        => [
				'name'           => 'g2rd_update-product',
				'description'    => 'Updates an existing FluentCart product and replaces its pricing variations. Same fields as g2rd_create-product plus product_id. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'product_id' => [
							'type'        => 'integer',
							'description' => 'Product post ID to update.',
							'minimum'     => 1,
						],
						'title'      => [
							'type'        => 'string',
							'description' => 'New product name.',
						],
						'content'    => [
							'type'        => 'string',
							'description' => 'New long description, HTML allowed.',
						],
						'excerpt'    => [
							'type'        => 'string',
							'description' => 'New short description.',
						],
						'status'     => [
							'type'        => 'string',
							'description' => 'New post status.',
							'enum'        => McpProducts::STATUSES,
						],
						'variations' => [
							'type'        => 'array',
							'description' => 'Replacement pricing entries. Same shape as g2rd_create-product. Omit to leave pricing untouched.',
							'items'       => [ 'type' => 'object' ],
						],
					],
					'required'   => [ 'product_id' ],
				],
			],
			'g2rd_delete-product'        => [
				'name'           => 'g2rd_delete-product',
				'description'    => 'Moves a FluentCart product to the trash. Permanent deletion is never performed. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'delete_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'product_id' => [
							'type'        => 'integer',
							'description' => 'Product post ID to trash.',
							'minimum'     => 1,
						],
					],
					'required'   => [ 'product_id' ],
				],
			],

			// ── WooCommerce products ──────────────────────────────────────────────
			'g2rd_list-woo-products'     => [
				'name'           => 'g2rd_list-woo-products',
				'description'    => 'Lists WooCommerce products with name, type, SKU, formatted price, stock state and URL. Requires WooCommerce to be active.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'per_page' => [
							'type'        => 'integer',
							'description' => 'Results per page (1-100, default 20).',
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'page'     => [
							'type'        => 'integer',
							'description' => 'Page number (default 1).',
							'minimum'     => 1,
						],
						'status'   => [
							'type'        => 'string',
							'description' => 'Post status filter, or "any" (default).',
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Free-text search on the product name.',
						],
					],
				],
			],
			'g2rd_get-woo-product'       => [
				'name'           => 'g2rd_get-woo-product',
				'description'    => 'Returns the full state of a WooCommerce product: prices, stock, dimensions, taxonomy, media, visibility, plus purchasable and on_sale flags. Read this before updating so you only change what you intend to.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'product_id' => [
							'type'        => 'integer',
							'description' => 'WooCommerce product ID.',
							'minimum'     => 1,
						],
					],
					'required'   => [ 'product_id' ],
				],
			],
			'g2rd_create-woo-product'    => [
				'name'           => 'g2rd_create-woo-product',
				'description'    => 'Creates a WooCommerce product through the official CRUD classes. PRICES ARE DECIMAL AMOUNTS IN THE SHOP CURRENCY, given as strings: "200.00" means 200 euros. This is the opposite of g2rd_create-product (FluentCart), which expects cents — sending 20000 here would create a 20 000 euro product. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_products',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'name'               => [
							'type'        => 'string',
							'description' => 'Product name (required).',
						],
						'type'               => [
							'type'        => 'string',
							'description' => 'Product type.',
							'enum'        => McpWooProducts::PRODUCT_TYPES,
						],
						'status'             => [
							'type'        => 'string',
							'description' => 'Post status.',
							'enum'        => McpWooProducts::STATUSES,
						],
						'regular_price'      => [
							'type'        => 'string',
							'description' => 'Regular price as a DECIMAL amount in the shop currency, e.g. "19.99" or "200.00". Never a number of cents.',
						],
						'sale_price'         => [
							'type'        => 'string',
							'description' => 'Sale price, same decimal format. Must be lower than regular_price or WooCommerce ignores it.',
						],
						'sku'                => [
							'type'        => 'string',
							'description' => 'Stock keeping unit. Must be unique across the shop.',
						],
						'description'        => [
							'type'        => 'string',
							'description' => 'Long description, HTML allowed.',
						],
						'short_description'  => [
							'type'        => 'string',
							'description' => 'Short description shown next to the price.',
						],
						'slug'               => [
							'type'        => 'string',
							'description' => 'URL slug. Defaults to a sanitized name.',
						],
						'categories'         => [
							'type'        => 'array',
							'description' => 'Product category slugs or term IDs.',
							'items'       => [ 'type' => 'string' ],
						],
						'tags'               => [
							'type'        => 'array',
							'description' => 'Product tag slugs or term IDs.',
							'items'       => [ 'type' => 'string' ],
						],
						'image_id'           => [
							'type'        => 'integer',
							'description' => 'Attachment ID for the main product image.',
						],
						'gallery_image_ids'  => [
							'type'        => 'array',
							'description' => 'Attachment IDs for the product gallery.',
							'items'       => [ 'type' => 'integer' ],
						],
						'manage_stock'       => [
							'type'        => 'boolean',
							'description' => 'Enable stock management for this product.',
						],
						'stock_quantity'     => [
							'type'        => 'integer',
							'description' => 'Stock quantity, used when manage_stock is true.',
						],
						'stock_status'       => [
							'type'        => 'string',
							'description' => 'Stock state when stock is not managed.',
							'enum'        => McpWooProducts::STOCK_STATUSES,
						],
						'backorders'         => [
							'type'        => 'string',
							'description' => 'Backorder policy.',
							'enum'        => McpWooProducts::BACKORDERS,
						],
						'virtual'            => [
							'type'        => 'boolean',
							'description' => 'Virtual product: no shipping.',
						],
						'downloadable'       => [
							'type'        => 'boolean',
							'description' => 'Downloadable product.',
						],
						'featured'           => [
							'type'        => 'boolean',
							'description' => 'Mark as featured.',
						],
						'catalog_visibility' => [
							'type'        => 'string',
							'description' => 'Where the product appears.',
							'enum'        => McpWooProducts::VISIBILITIES,
						],
						'sold_individually'  => [
							'type'        => 'boolean',
							'description' => 'Limit to one per order.',
						],
						'reviews_allowed'    => [
							'type'        => 'boolean',
							'description' => 'Allow customer reviews.',
						],
						'tax_status'         => [
							'type'        => 'string',
							'description' => 'Tax handling.',
							'enum'        => McpWooProducts::TAX_STATUSES,
						],
						'tax_class'          => [
							'type'        => 'string',
							'description' => 'Tax class slug, empty for the standard rate.',
						],
						'weight'             => [
							'type'        => 'string',
							'description' => 'Weight in the shop unit.',
						],
						'length'             => [
							'type'        => 'string',
							'description' => 'Length in the shop unit.',
						],
						'width'              => [
							'type'        => 'string',
							'description' => 'Width in the shop unit.',
						],
						'height'             => [
							'type'        => 'string',
							'description' => 'Height in the shop unit.',
						],
						'purchase_note'      => [
							'type'        => 'string',
							'description' => 'Note sent to the customer after purchase.',
						],
						'menu_order'         => [
							'type'        => 'integer',
							'description' => 'Sort order in listings.',
						],
					],
					'required'   => [ 'name', 'regular_price' ],
				],
			],
			'g2rd_update-woo-product'    => [
				'name'           => 'g2rd_update-woo-product',
				'description'    => 'Updates a WooCommerce product. Accepts the same fields as g2rd_create-woo-product plus product_id; only the fields you supply are written, everything else is left untouched. Prices are DECIMAL amounts, never cents. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_products',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'product_id'        => [
							'type'        => 'integer',
							'description' => 'WooCommerce product ID to update.',
							'minimum'     => 1,
						],
						'name'              => [
							'type'        => 'string',
							'description' => 'New product name.',
						],
						'status'            => [
							'type'        => 'string',
							'description' => 'New post status.',
							'enum'        => McpWooProducts::STATUSES,
						],
						'regular_price'     => [
							'type'        => 'string',
							'description' => 'New regular price as a DECIMAL amount, e.g. "24.90". Never cents.',
						],
						'sale_price'        => [
							'type'        => 'string',
							'description' => 'New sale price, same decimal format. Must be lower than the regular price.',
						],
						'description'       => [
							'type'        => 'string',
							'description' => 'New long description, HTML allowed.',
						],
						'short_description' => [
							'type'        => 'string',
							'description' => 'New short description.',
						],
						'sku'               => [
							'type'        => 'string',
							'description' => 'New SKU.',
						],
						'stock_quantity'    => [
							'type'        => 'integer',
							'description' => 'New stock quantity.',
						],
						'stock_status'      => [
							'type'        => 'string',
							'description' => 'New stock state.',
							'enum'        => McpWooProducts::STOCK_STATUSES,
						],
						'categories'        => [
							'type'        => 'array',
							'description' => 'Replacement category slugs or IDs.',
							'items'       => [ 'type' => 'string' ],
						],
						'image_id'          => [
							'type'        => 'integer',
							'description' => 'New main image attachment ID.',
						],
					],
					'required'   => [ 'product_id' ],
				],
			],
			'g2rd_delete-woo-product'    => [
				'name'           => 'g2rd_delete-woo-product',
				'description'    => 'Moves a WooCommerce product to the trash. Permanent deletion is never performed. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'delete_products',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'product_id' => [
							'type'        => 'integer',
							'description' => 'WooCommerce product ID to trash.',
							'minimum'     => 1,
						],
					],
					'required'   => [ 'product_id' ],
				],
			],

			// ── Allowlisted plugin settings ───────────────────────────────────────
			'g2rd_list-plugin-settings'  => [
				'name'           => 'g2rd_list-plugin-settings',
				'description'    => 'Lists every plugin setting this server is allowed to read or write, with its type, allowed values and backing option. Introspection only: never returns current values nor any credential. Use it before calling get-plugin-setting or update-plugin-setting.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'manage_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'active_only' => [
							'type'        => 'boolean',
							'description' => 'When true, only list plugins currently active on this site. Default false.',
						],
					],
				],
			],
			'g2rd_get-plugin-setting'    => [
				'name'           => 'g2rd_get-plugin-setting',
				'description'    => 'Reads the current value of one allowlisted plugin setting. Any setting outside the allowlist is refused. Never returns passwords, API keys, tokens or licence data.',
				'required_scope' => 'read_only',
				'wp_capability'  => 'manage_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'plugin'  => [
							'type'        => 'string',
							'description' => 'Plugin slug.',
							'enum'        => McpPluginSettings::plugin_slugs(),
						],
						'setting' => [
							'type'        => 'string',
							'description' => 'Allowlisted setting slug.',
							'enum'        => McpPluginSettings::setting_slugs(),
						],
					],
					'required'   => [ 'plugin', 'setting' ],
				],
			],
			'g2rd_update-plugin-setting' => [
				'name'           => 'g2rd_update-plugin-setting',
				'description'    => 'Updates one allowlisted plugin setting. Only the targeted sub-key changes; every sibling setting of the same option is preserved. Any setting outside the allowlist is refused. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'manage_options',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'plugin'  => [
							'type'        => 'string',
							'description' => 'Plugin slug.',
							'enum'        => McpPluginSettings::plugin_slugs(),
						],
						'setting' => [
							'type'        => 'string',
							'description' => 'Allowlisted setting slug.',
							'enum'        => McpPluginSettings::setting_slugs(),
						],
						'value'   => [
							'description' => 'New value. Boolean for toggles, string for text and enum settings, array of post type slugs for post type lists.',
							'type'        => [ 'string', 'boolean', 'array' ],
						],
					],
					'required'   => [ 'plugin', 'setting', 'value' ],
				],
			],

			// ── Media upload ──────────────────────────────────────────────────────
			'g2rd_upload-media'      => [
				'name'           => 'g2rd_upload-media',
				'description'    => 'Downloads an image from a URL and imports it into the WordPress media library. Allowed types: jpg, jpeg, png, gif, webp, svg, pdf. Max 10 MB. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'upload_files',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'url'         => [
							'type'        => 'string',
							'description' => 'URL of the file to download and import (required).',
						],
						'title'       => [
							'type'        => 'string',
							'description' => 'Attachment title.',
						],
						'alt_text'    => [
							'type'        => 'string',
							'description' => 'Alt text for the image.',
						],
						'caption'     => [
							'type'        => 'string',
							'description' => 'Image caption (post_excerpt).',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'Image description (post_content).',
						],
					],
					'required'   => [ 'url' ],
				],
			],

			'g2rd_upload-media-base64' => [
				'name'           => 'g2rd_upload-media-base64',
				'description'    => 'Imports a file from base64-encoded content into the WordPress media library. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'upload_files',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'data'      => [
							'type'        => 'string',
							'description' => 'Base64-encoded file content (required).',
						],
						'filename'  => [
							'type'        => 'string',
							'description' => 'Filename including extension, e.g. "photo.png" (required).',
						],
						'mime_type' => [
							'type'        => 'string',
							'description' => 'MIME type, e.g. "image/png" (required).',
						],
						'title'     => [
							'type'        => 'string',
							'description' => 'Attachment title.',
						],
						'alt_text'  => [
							'type'        => 'string',
							'description' => 'Alt text for the image.',
						],
					],
					'required'   => [ 'data', 'filename', 'mime_type' ],
				],
			],

			'g2rd_delete-media'      => [
				'name'           => 'g2rd_delete-media',
				'description'    => 'Moves a media attachment to the trash (never permanently deleted). Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'delete_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'attachment_id' => [
							'type'        => 'integer',
							'description' => 'ID of the media attachment to trash (required).',
							'minimum'     => 1,
						],
					],
					'required'   => [ 'attachment_id' ],
				],
			],

			'g2rd_create-full-post'  => [
				'name'           => 'g2rd_create-full-post',
				'description'    => 'Creates a complete WordPress post in a single confirmation: title, content, excerpt, status, slug, categories, tags, a featured image sideloaded from a URL, and SEO meta (via the detected SEO plugin). One email confirmation covers the whole operation. Requires administrator email confirmation before execution.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'title'                  => [
							'type'        => 'string',
							'description' => 'Post title (required).',
						],
						'content'                => [
							'type'        => 'string',
							'description' => 'Post content: HTML or Gutenberg block markup (required).',
						],
						'excerpt'                => [
							'type'        => 'string',
							'description' => 'Post excerpt.',
						],
						'status'                 => [
							'type'        => 'string',
							'description' => 'Post status: draft, pending or publish (default: draft).',
							'default'     => 'draft',
							'enum'        => [ 'draft', 'pending', 'publish' ],
						],
						'post_type'              => [
							'type'        => 'string',
							'description' => 'Post type slug (default: post).',
							'default'     => 'post',
						],
						'slug'                   => [
							'type'        => 'string',
							'description' => 'URL-safe post slug.',
						],
						'categories'             => [
							'type'        => 'array',
							'description' => 'Array of existing category IDs to assign.',
							'items'       => [ 'type' => 'integer' ],
						],
						'tags'                   => [
							'type'        => 'array',
							'description' => 'Array of tag names or slugs (missing tags are created).',
							'items'       => [ 'type' => 'string' ],
						],
						'featured_image_url'     => [
							'type'        => 'string',
							'description' => 'URL of an image (jpg, png, webp, gif, max 10 MB) to import and set as the featured image.',
						],
						'featured_image_title'   => [
							'type'        => 'string',
							'description' => 'Title for the imported featured image.',
						],
						'featured_image_alt'     => [
							'type'        => 'string',
							'description' => 'Alt text for the imported featured image.',
						],
						'featured_image_caption' => [
							'type'        => 'string',
							'description' => 'Caption for the imported featured image.',
						],
						'seo'                    => [
							'type'        => 'object',
							'description' => 'SEO meta written via the detected SEO plugin (Yoast, Rank Math, SEOPress, AIOSEO).',
							'properties'  => [
								'meta_title'       => [
									'type'        => 'string',
									'description' => 'SEO title tag.',
								],
								'meta_description' => [
									'type'        => 'string',
									'description' => 'SEO meta description.',
								],
								'focus_keyword'    => [
									'type'        => 'string',
									'description' => 'Focus keyword or keyphrase.',
								],
								'og_title'         => [
									'type'        => 'string',
									'description' => 'Open Graph title.',
								],
								'og_description'   => [
									'type'        => 'string',
									'description' => 'Open Graph description.',
								],
								'canonical'        => [
									'type'        => 'string',
									'description' => 'Canonical URL.',
								],
								'noindex'          => [
									'type'        => 'boolean',
									'description' => 'Set to true to mark the page as noindex.',
								],
							],
						],
					],
					'required'   => [ 'title', 'content' ],
				],
			],

			'g2rd_batch'             => [
				'name'           => 'g2rd_batch',
				'description'    => 'Groups several write operations into a single administrator email confirmation. Operations run sequentially after approval (best-effort, no global rollback). Nested batches are not allowed; max 20 operations. Requires administrator email confirmation.',
				'required_scope' => 'editor',
				'wp_capability'  => 'edit_posts',
				'inputSchema'    => [
					'type'       => 'object',
					'properties' => [
						'operations' => [
							'type'        => 'array',
							'description' => 'List of write operations to execute (max 20). Each item has a tool name and its arguments.',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'tool'      => [
										'type'        => 'string',
										'description' => 'Write tool name, e.g. "g2rd_create-post" (g2rd_batch itself is not allowed).',
									],
									'arguments' => [
										'type'        => 'object',
										'description' => 'Arguments object passed to that tool.',
									],
								],
								'required'   => [ 'tool', 'arguments' ],
							],
						],
					],
					'required'   => [ 'operations' ],
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
	 * @param string $name Tool name (e.g. 'g2rd_get-post').
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
			// ── Read tools ─────────────────────────────────────────────────────
			case 'g2rd_get-site-info':
				return $this->exec_get_site_info();
			case 'g2rd_list-posts':
				return $this->exec_list_posts( $args, $gate_result );
			case 'g2rd_get-post':
				return $this->exec_get_post( $args, $gate_result );
			case 'g2rd_get-post-meta':
				return $this->exec_get_post_meta( $args, $gate_result );
			case 'g2rd_list-categories':
				return $this->exec_list_categories();
			case 'g2rd_list-tags':
				return $this->exec_list_tags();
			case 'g2rd_list-media':
				return $this->exec_list_media( $args );
			case 'g2rd_get-media':
				return $this->exec_get_media( $args );
			case 'g2rd_get-seo-data':
				return $this->exec_get_seo_data( $args, $gate_result );
			case 'g2rd_get-seo-overview':
				return $this->exec_get_seo_overview();
			case 'g2rd_get-redirections':
				return $this->exec_get_redirections();
			case 'g2rd_list-plugins':
				return $this->exec_list_plugins( $gate_result );
			case 'g2rd_get-theme-info':
				return $this->exec_get_theme_info();
			case 'g2rd_list-themes':
				return $this->exec_list_themes();
			case 'g2rd_get-options':
				return $this->exec_get_options( $gate_result );
			case 'g2rd_get-users':
				return $this->exec_get_users( $args, $gate_result );
			case 'g2rd_get-site-health':
				return $this->exec_get_site_health( $gate_result );
			case 'g2rd_get-cron-jobs':
				return $this->exec_get_cron_jobs( $gate_result );
			case 'g2rd_list-menus':
				return $this->exec_list_menus();
			case 'g2rd_list-plugin-settings':
				return $this->exec_list_plugin_settings( $args, $gate_result );
			case 'g2rd_get-plugin-setting':
				return $this->exec_get_plugin_setting( $args, $gate_result );
			case 'g2rd_list-post-types':
				return $this->exec_list_post_types( $gate_result );
			case 'g2rd_list-products':
				return $this->exec_list_products( $args, $gate_result );
			case 'g2rd_get-product':
				return $this->exec_get_product( $args, $gate_result );
			case 'g2rd_list-woo-products':
				return $this->exec_list_woo_products( $args, $gate_result );
			case 'g2rd_get-woo-product':
				return $this->exec_get_woo_product( $args, $gate_result );

			// ── Write tools (enqueue for confirmation) ─────────────────────────
			case 'g2rd_create-post':
			case 'g2rd_update-post':
			case 'g2rd_delete-post':
			case 'g2rd_update-post-meta':
			case 'g2rd_update-seo-data':
			case 'g2rd_create-redirection':
			case 'g2rd_create-category':
			case 'g2rd_create-tag':
			case 'g2rd_update-media':
			case 'g2rd_activate-plugin':
			case 'g2rd_deactivate-plugin':
			case 'g2rd_update-plugin':
			case 'g2rd_update-option':
			case 'g2rd_create-product':
			case 'g2rd_update-product':
			case 'g2rd_delete-product':
			case 'g2rd_create-woo-product':
			case 'g2rd_update-woo-product':
			case 'g2rd_delete-woo-product':
			case 'g2rd_update-plugin-setting':
			case 'g2rd_flush-cache':
			case 'g2rd_update-menu-item':
			case 'g2rd_upload-media':
			case 'g2rd_upload-media-base64':
			case 'g2rd_delete-media':
			case 'g2rd_create-full-post':
			case 'g2rd_batch':
				return $this->exec_enqueue_write( $name, $args, $gate_result );

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
			'php_version' => \phpversion(),
			'timezone'    => \wp_timezone_string(),
			'is_multisite' => \is_multisite(),
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns a paginated list of posts (tool: g2rd/list-posts).
	 *
	 * Non-publish statuses require editor scope and edit_posts capability.
	 *
	 * @param array<string, mixed> $args        Tool arguments.
	 * @param array<string, mixed> $gate_result Authorized gate result (scope, user_id…).
	 * @return array<string, mixed>
	 */
	private function exec_list_posts( array $args, array $gate_result ): array {
		$post_type = \sanitize_key( (string) ( $args['post_type'] ?? 'post' ) );
		$per_page  = \min( 50, \max( 1, \absint( $args['per_page'] ?? 10 ) ) );
		$page      = \max( 1, \absint( $args['page'] ?? 1 ) );

		$valid_statuses = [ 'publish', 'draft', 'pending', 'private', 'future', 'trash', 'any' ];
		$status         = \sanitize_key( (string) ( $args['status'] ?? 'publish' ) );
		if ( ! \in_array( $status, $valid_statuses, true ) ) {
			$status = 'publish';
		}

		$is_editor = 'editor' === ( $gate_result['scope'] ?? '' );

		if ( 'publish' !== $status ) {
			if ( ! $is_editor ) {
				return $this->tool_error( 'Listing non-published posts requires editor scope.' );
			}
			$user_id = (int) ( $gate_result['user_id'] ?? 0 );
			$user    = $user_id > 0 ? \get_userdata( $user_id ) : false;
			if ( ! $user instanceof \WP_User || ! $user->has_cap( 'edit_posts' ) ) {
				return $this->tool_error( 'Insufficient permissions to list non-published posts.' );
			}
		}

		/*
		 * Two distinct failures deserve two distinct messages. Returning
		 * "not accessible" for a type that simply does not exist sends the caller
		 * hunting for a permission problem, and forces it to guess the real slug.
		 */
		$pto = \get_post_type_object( $post_type );

		if ( null === $pto ) {
			return $this->tool_error(
				\sprintf(
					'Unknown post type "%s". Registered and readable types: %s. Use g2rd_list-post-types for the full list.',
					$post_type,
					\implode( ', ', $this->readable_post_types() )
				)
			);
		}

		if ( ! $pto->publicly_queryable && ! $pto->public ) {
			return $this->tool_error(
				\sprintf(
					'Post type "%s" exists but is not publicly queryable, so it cannot be listed. Readable types: %s.',
					$post_type,
					\implode( ', ', $this->readable_post_types() )
				)
			);
		}

		$query_args = [
			'post_type'              => $post_type,
			'post_status'            => $status,
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'update_post_term_cache' => false,
		];

		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = \sanitize_text_field( (string) $args['search'] );
		}
		if ( ! empty( $args['category'] ) ) {
			$query_args['category_name'] = \sanitize_key( (string) $args['category'] );
		}
		if ( ! empty( $args['tag'] ) ) {
			$query_args['tag'] = \sanitize_key( (string) $args['tag'] );
		}

		$query = new \WP_Query( $query_args );

		$posts = [];
		foreach ( $query->posts as $post ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				continue;
			}
			$posts[] = [
				'id'      => $post->ID,
				'title'   => \get_the_title( $post ),
				'slug'    => $post->post_name,
				'status'  => $post->post_status,
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
	 * Returns a single post with full content and taxonomy (tool: g2rd/get-post).
	 *
	 * Read-only scope: publish only.
	 * Editor scope: also draft, pending, private, future — requires edit_posts (own) or edit_others_posts.
	 *
	 * @param array<string, mixed> $args        Tool arguments.
	 * @param array<string, mixed> $gate_result Authorized gate result (scope, user_id…).
	 * @return array<string, mixed>
	 */
	private function exec_get_post( array $args, array $gate_result ): array {
		$post_id = \absint( $args['post_id'] ?? 0 );

		if ( $post_id <= 0 ) {
			return $this->tool_error( 'Missing required argument: post_id' );
		}

		$post = \get_post( $post_id );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return $this->tool_error( "Post not found: {$post_id}" );
		}

		$is_editor        = 'editor' === ( $gate_result['scope'] ?? '' );
		$allowed_statuses = $is_editor
			? [ 'publish', 'draft', 'pending', 'private', 'future' ]
			: [ 'publish' ];

		if ( ! \in_array( $post->post_status, $allowed_statuses, true ) ) {
			return $this->tool_error( "Post not accessible: {$post_id}" );
		}

		if ( $is_editor && 'publish' !== $post->post_status ) {
			$user_id  = (int) ( $gate_result['user_id'] ?? 0 );
			$user     = $user_id > 0 ? \get_userdata( $user_id ) : false;
			$is_owner = $user instanceof \WP_User && ( (int) $post->post_author === $user_id );
			$can_edit = $user instanceof \WP_User && (
				( $is_owner && $user->has_cap( 'edit_posts' ) ) ||
				( ! $is_owner && $user->has_cap( 'edit_others_posts' ) )
			);
			if ( ! $can_edit ) {
				return $this->tool_error( "Post not accessible: {$post_id}" );
			}
		}

		$author = \get_userdata( (int) $post->post_author );

		// Taxonomy.
		$cats = \get_the_category( $post->ID );
		$categories = [];
		if ( \is_array( $cats ) ) {
			foreach ( $cats as $cat ) {
				$categories[] = [
					'id'   => $cat->term_id,
					'name' => $cat->name,
					'slug' => $cat->slug,
				];
			}
		}

		$tag_objects = \get_the_tags( $post->ID );
		$tags        = [];
		if ( \is_array( $tag_objects ) ) {
			foreach ( $tag_objects as $tag ) {
				$tags[] = [
					'id'   => $tag->term_id,
					'name' => $tag->name,
					'slug' => $tag->slug,
				];
			}
		}

		// Featured image.
		$feat_id  = (int) \get_post_thumbnail_id( $post->ID );
		$feat_url = $feat_id > 0 ? (string) \get_the_post_thumbnail_url( $post->ID, 'full' ) : '';

		// SEO meta (detected plugin).
		$seo_plugin = $this->detect_active_seo_plugin();
		$seo_meta   = 'none' !== $seo_plugin ? $this->get_seo_meta_for_post( $post->ID, $seo_plugin ) : [];

		$data = [
			'id'               => $post->ID,
			'title'            => \get_the_title( $post ),
			'slug'             => $post->post_name,
			'status'           => $post->post_status,
			'date'             => $post->post_date_gmt,
			'modified'         => $post->post_modified_gmt,
			'content_html'     => \apply_filters( 'the_content', $post->post_content ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core WordPress filter
			'content_text'     => \wp_strip_all_tags( $post->post_content ),
			'excerpt'          => \get_the_excerpt( $post ),
			'url'              => \get_permalink( $post ),
			'post_type'        => $post->post_type,
			'parent_id'        => $post->post_parent,
			'author_id'        => (int) $post->post_author,
			'author_name'      => $author ? (string) $author->display_name : '',
			'categories'       => $categories,
			'tags'             => $tags,
			'featured_image_id' => $feat_id,
			'featured_image_url' => $feat_url,
			'template'         => (string) \get_page_template_slug( $post->ID ),
			'seo'              => $seo_meta,
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns all meta fields for a post (tool: g2rd/get-post-meta).
	 *
	 * Published posts: accessible with read_only scope.
	 * Non-published posts: require editor scope.
	 *
	 * @param array<string, mixed> $args        Tool arguments.
	 * @param array<string, mixed> $gate_result Authorized gate result (scope, user_id…).
	 * @return array<string, mixed>
	 */
	private function exec_get_post_meta( array $args, array $gate_result ): array {
		$post_id = \absint( $args['post_id'] ?? 0 );

		if ( $post_id <= 0 ) {
			return $this->tool_error( 'Missing required argument: post_id' );
		}

		$post = \get_post( $post_id );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return $this->tool_error( "Post not found: {$post_id}" );
		}

		$is_editor = 'editor' === ( $gate_result['scope'] ?? '' );

		if ( 'publish' !== $post->post_status && ! $is_editor ) {
			return $this->tool_error( "Post not accessible: {$post_id}" );
		}

		$raw_meta = \get_post_meta( $post_id );
		$meta     = [];

		if ( \is_array( $raw_meta ) ) {
			foreach ( $raw_meta as $key => $values ) {
				// Return the first value, unserialized; skip private _ keys for read_only scope.
				if ( ! $is_editor && \str_starts_with( $key, '_' ) ) {
					continue;
				}
				$value    = isset( $values[0] ) ? \maybe_unserialize( $values[0] ) : null;
				$meta[ $key ] = $value;
			}
		}

		$data = [
			'post_id' => $post_id,
			'meta'    => $meta,
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns all categories (tool: g2rd/list-categories).
	 *
	 * @return array<string, mixed>
	 */
	private function exec_list_categories(): array {
		$terms = \get_terms( [
			'taxonomy'   => 'category',
			'hide_empty' => false,
		] );

		if ( \is_wp_error( $terms ) ) {
			return $this->tool_error( 'Failed to retrieve categories.' );
		}

		$categories = [];
		if ( \is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = [
					'id'          => $term->term_id,
					'name'        => $term->name,
					'slug'        => $term->slug,
					'description' => $term->description,
					'parent_id'   => $term->parent,
					'count'       => $term->count,
				];
			}
		}

		return $this->tool_success( (string) \wp_json_encode( [ 'categories' => $categories, 'total' => \count( $categories ) ] ) );
	}

	/**
	 * Returns all tags (tool: g2rd/list-tags).
	 *
	 * @return array<string, mixed>
	 */
	private function exec_list_tags(): array {
		$terms = \get_terms( [
			'taxonomy'   => 'post_tag',
			'hide_empty' => false,
		] );

		if ( \is_wp_error( $terms ) ) {
			return $this->tool_error( 'Failed to retrieve tags.' );
		}

		$tags = [];
		if ( \is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$tags[] = [
					'id'          => $term->term_id,
					'name'        => $term->name,
					'slug'        => $term->slug,
					'description' => $term->description,
					'count'       => $term->count,
				];
			}
		}

		return $this->tool_success( (string) \wp_json_encode( [ 'tags' => $tags, 'total' => \count( $tags ) ] ) );
	}

	/**
	 * Returns a paginated list of media library items (tool: g2rd/list-media).
	 *
	 * @param array<string, mixed> $args Tool arguments.
	 * @return array<string, mixed>
	 */
	private function exec_list_media( array $args ): array {
		$per_page = \min( 50, \max( 1, \absint( $args['per_page'] ?? 20 ) ) );
		$page     = \max( 1, \absint( $args['page'] ?? 1 ) );

		$query_args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $page,
		];

		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = \sanitize_text_field( (string) $args['search'] );
		}

		$query = new \WP_Query( $query_args );

		$items = [];
		foreach ( $query->posts as $post ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				continue;
			}
			$meta       = \wp_get_attachment_metadata( $post->ID );
			$width      = \is_array( $meta ) && isset( $meta['width'] ) ? (int) $meta['width'] : null;
			$height     = \is_array( $meta ) && isset( $meta['height'] ) ? (int) $meta['height'] : null;
			$file_size  = \is_array( $meta ) && isset( $meta['filesize'] ) ? (int) $meta['filesize'] : null;

			$items[] = [
				'id'        => $post->ID,
				'url'       => \wp_get_attachment_url( $post->ID ),
				'title'     => $post->post_title,
				'alt'       => (string) \get_post_meta( $post->ID, '_wp_attachment_image_alt', true ),
				'caption'   => $post->post_excerpt,
				'mime_type' => $post->post_mime_type,
				'width'     => $width,
				'height'    => $height,
				'file_size' => $file_size,
				'date'      => $post->post_date_gmt,
			];
		}

		$data = [
			'items'       => $items,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns details for a single media attachment (tool: g2rd/get-media).
	 *
	 * @param array<string, mixed> $args Tool arguments.
	 * @return array<string, mixed>
	 */
	private function exec_get_media( array $args ): array {
		$media_id = \absint( $args['media_id'] ?? 0 );

		if ( $media_id <= 0 ) {
			return $this->tool_error( 'Missing required argument: media_id' );
		}

		$post = \get_post( $media_id );

		if ( ! ( $post instanceof \WP_Post ) || 'attachment' !== $post->post_type ) {
			return $this->tool_error( "Media not found: {$media_id}" );
		}

		$meta      = \wp_get_attachment_metadata( $media_id );
		$width     = \is_array( $meta ) && isset( $meta['width'] ) ? (int) $meta['width'] : null;
		$height    = \is_array( $meta ) && isset( $meta['height'] ) ? (int) $meta['height'] : null;
		$file_size = \is_array( $meta ) && isset( $meta['filesize'] ) ? (int) $meta['filesize'] : null;
		$sizes     = \is_array( $meta ) && isset( $meta['sizes'] ) ? \array_keys( $meta['sizes'] ) : [];

		$data = [
			'id'          => $post->ID,
			'url'         => \wp_get_attachment_url( $media_id ),
			'title'       => $post->post_title,
			'alt'         => (string) \get_post_meta( $media_id, '_wp_attachment_image_alt', true ),
			'caption'     => $post->post_excerpt,
			'description' => $post->post_content,
			'mime_type'   => $post->post_mime_type,
			'width'       => $width,
			'height'      => $height,
			'file_size'   => $file_size,
			'sizes'       => $sizes,
			'date'        => $post->post_date_gmt,
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns SEO meta for a post via the active SEO plugin (tool: g2rd/get-seo-data).
	 *
	 * Supports Yoast SEO, Rank Math, SEOPress and All in One SEO.
	 *
	 * @param array<string, mixed> $args        Tool arguments.
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_get_seo_data( array $args, array $gate_result ): array {
		$post_id = \absint( $args['post_id'] ?? 0 );

		if ( $post_id <= 0 ) {
			return $this->tool_error( 'Missing required argument: post_id' );
		}

		$post = \get_post( $post_id );

		if ( ! ( $post instanceof \WP_Post ) ) {
			return $this->tool_error( "Post not found: {$post_id}" );
		}

		$is_editor = 'editor' === ( $gate_result['scope'] ?? '' );
		if ( 'publish' !== $post->post_status && ! $is_editor ) {
			return $this->tool_error( "Post not accessible: {$post_id}" );
		}

		$plugin = $this->detect_active_seo_plugin();
		$seo    = $this->get_seo_meta_for_post( $post_id, $plugin );

		$data = \array_merge(
			[ 'post_id' => $post_id, 'seo_plugin' => $plugin ],
			$seo
		);

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns a site-wide SEO audit (tool: g2rd/get-seo-overview).
	 *
	 * Checks up to 500 most-recent published posts for SEO completeness.
	 *
	 * @return array<string, mixed>
	 */
	private function exec_get_seo_overview(): array {
		$plugin = $this->detect_active_seo_plugin();

		$query = new \WP_Query( [
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- limited batch, not a query default
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		] );

		$ids         = $query->posts;
		$total       = (int) $query->found_posts;
		$checked     = \count( $ids );
		$no_title    = 0;
		$no_desc     = 0;
		$noindex     = 0;
		$no_feat_img = 0;

		foreach ( $ids as $pid ) {
			$pid = (int) $pid;

			if ( ! \has_post_thumbnail( $pid ) ) {
				++$no_feat_img;
			}

			if ( 'none' !== $plugin ) {
				$seo = $this->get_seo_meta_for_post( $pid, $plugin );
				if ( '' === ( $seo['title'] ?? '' ) ) {
					++$no_title;
				}
				if ( '' === ( $seo['description'] ?? '' ) ) {
					++$no_desc;
				}
				if ( ! empty( $seo['noindex'] ) ) {
					++$noindex;
				}
			}
		}

		$data = [
			'seo_plugin'         => $plugin,
			'total_posts'        => $total,
			'checked_posts'      => $checked,
			'no_seo_title'       => $no_title,
			'no_meta_description' => $no_desc,
			'noindex_count'      => $noindex,
			'no_featured_image'  => $no_feat_img,
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns active URL redirections (tool: g2rd/get-redirections).
	 *
	 * Queries Redirection plugin and Rank Math tables if present.
	 *
	 * @return array<string, mixed>
	 */
	private function exec_get_redirections(): array {
		global $wpdb;

		$redirections = [];

		// Redirection plugin.
		$rd_table = $wpdb->prefix . 'redirection_items';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rd_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $rd_table )
		);
		if ( $rd_table === $rd_exists ) {
			$rows = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from server constant
				"SELECT url, action_data, action_type, last_count FROM `{$rd_table}` WHERE status = 'enabled' LIMIT 200",
				\ARRAY_A
			);
			if ( \is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					$redirections[] = [
						'source' => (string) $row['url'],
						'target' => (string) $row['action_data'],
						'type'   => 'url' === $row['action_type'] ? '301' : (string) $row['action_type'],
						'hits'   => (int) $row['last_count'],
						'plugin' => 'redirection',
					];
				}
			}
		}

		// Rank Math redirections.
		$rm_table = $wpdb->prefix . 'rank_math_redirections';
		$rm_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $rm_table )
		);
		if ( $rm_table === $rm_exists ) {
			$rows = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from server constant
				"SELECT sources, url_to, header_code FROM `{$rm_table}` WHERE status = 'active' LIMIT 200",
				\ARRAY_A
			);
			if ( \is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					$sources = \json_decode( (string) $row['sources'], true );
					$source  = ( \is_array( $sources ) && isset( $sources[0]['pattern'] ) )
						? (string) $sources[0]['pattern']
						: '';
					$redirections[] = [
						'source' => $source,
						'target' => (string) $row['url_to'],
						'type'   => (string) $row['header_code'],
						'hits'   => 0,
						'plugin' => 'rank_math',
					];
				}
			}
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$data = [
			'redirections' => $redirections,
			'total'        => \count( $redirections ),
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns all installed plugins with update status (tool: g2rd/list-plugins).
	 *
	 * Requires manage_options capability.
	 *
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_list_plugins( array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'manage_options' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		if ( ! \function_exists( 'get_plugins' ) ) {
			require_once \ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins      = \get_plugins();
		$update_transient = \get_site_transient( 'update_plugins' );
		$active_plugins   = (array) \get_option( 'active_plugins', [] );

		$plugins = [];
		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$has_update  = isset( $update_transient->response[ $plugin_file ] );
			$new_version = $has_update ? (string) $update_transient->response[ $plugin_file ]->new_version : null;
			$slug        = \dirname( $plugin_file );
			if ( '.' === $slug ) {
				$slug = \basename( $plugin_file, '.php' );
			}

			$plugins[] = [
				'slug'             => $slug,
				'file'             => $plugin_file,
				'name'             => (string) $plugin_data['Name'],
				'version'          => (string) $plugin_data['Version'],
				'author'           => \wp_strip_all_tags( (string) $plugin_data['Author'] ),
				'description'      => \wp_strip_all_tags( (string) $plugin_data['Description'] ),
				'active'           => \in_array( $plugin_file, $active_plugins, true ),
				'update_available' => $has_update,
				'new_version'      => $new_version,
			];
		}

		return $this->tool_success( (string) \wp_json_encode( [ 'plugins' => $plugins, 'total' => \count( $plugins ) ] ) );
	}

	/**
	 * Returns active theme details (tool: g2rd/get-theme-info).
	 *
	 * @return array<string, mixed>
	 */
	private function exec_get_theme_info(): array {
		$theme          = \wp_get_theme();
		$update         = \get_site_transient( 'update_themes' );
		$stylesheet     = \get_stylesheet();
		$has_update     = isset( $update->response[ $stylesheet ] );
		$new_version    = $has_update ? (string) $update->response[ $stylesheet ]['new_version'] : null;

		$data = [
			'name'             => $theme->get( 'Name' ),
			'version'          => $theme->get( 'Version' ),
			'template'         => $theme->get_template(),
			'stylesheet'       => $stylesheet,
			'is_child_theme'   => $theme->get_template() !== $stylesheet,
			'parent_theme'     => $theme->get_template() !== $stylesheet ? $theme->get_template() : null,
			'author'           => $theme->get( 'Author' ),
			'description'      => \wp_strip_all_tags( (string) $theme->get( 'Description' ) ),
			'update_available' => $has_update,
			'new_version'      => $new_version,
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns all installed themes (tool: g2rd/list-themes).
	 *
	 * @return array<string, mixed>
	 */
	private function exec_list_themes(): array {
		$all_themes = \wp_get_themes();
		$stylesheet = \get_stylesheet();

		$themes = [];
		foreach ( $all_themes as $slug => $theme ) {
			$themes[] = [
				'slug'    => $slug,
				'name'    => $theme->get( 'Name' ),
				'version' => $theme->get( 'Version' ),
				'author'  => $theme->get( 'Author' ),
				'active'  => $slug === $stylesheet,
			];
		}

		return $this->tool_success( (string) \wp_json_encode( [ 'themes' => $themes, 'total' => \count( $themes ) ] ) );
	}

	/**
	 * Returns whitelisted WordPress site options (tool: g2rd/get-options).
	 *
	 * Never exposes passwords, API keys or security salts.
	 * Requires manage_options capability.
	 *
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_get_options( array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'manage_options' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		$safe_keys = [
			'blogname',
			'blogdescription',
			'siteurl',
			'home',
			'admin_email',
			'timezone_string',
			'gmt_offset',
			'date_format',
			'time_format',
			'start_of_week',
			'posts_per_page',
			'posts_per_rss',
			'default_comment_status',
			'default_ping_status',
			'permalink_structure',
			'blogcharset',
			'default_category',
			'default_post_format',
			'show_on_front',
			'page_on_front',
			'page_for_posts',
			'blog_public',
		];

		$options = [];
		foreach ( $safe_keys as $key ) {
			$options[ $key ] = \get_option( $key );
		}

		return $this->tool_success( (string) \wp_json_encode( $options ) );
	}

	/**
	 * Returns a paginated list of WordPress users (tool: g2rd/get-users).
	 *
	 * Never exposes passwords or authentication tokens.
	 * Requires list_users capability.
	 *
	 * @param array<string, mixed> $args        Tool arguments.
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_get_users( array $args, array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'list_users' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		$per_page = \min( 100, \max( 1, \absint( $args['per_page'] ?? 20 ) ) );
		$page     = \max( 1, \absint( $args['page'] ?? 1 ) );
		$offset   = ( $page - 1 ) * $per_page;

		$user_query = new \WP_User_Query( [
			'number' => $per_page,
			'offset' => $offset,
			'fields' => 'all_with_meta',
		] );

		$users = [];
		foreach ( $user_query->get_results() as $user ) {
			$roles = \is_array( $user->roles ) ? $user->roles : [];
			$users[] = [
				'id'           => $user->ID,
				'display_name' => $user->display_name,
				'email'        => $user->user_email,
				'login'        => $user->user_login,
				'roles'        => $roles,
				'registered'   => $user->user_registered,
				'post_count'   => (int) \count_user_posts( $user->ID ),
			];
		}

		$data = [
			'users'       => $users,
			'total'       => (int) $user_query->get_total(),
			'page'        => $page,
			'per_page'    => $per_page,
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns WordPress and server health information (tool: g2rd/get-site-health).
	 *
	 * Requires manage_options capability.
	 *
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_get_site_health( array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'manage_options' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		global $wpdb, $wp_version;

		$core_update    = \get_site_transient( 'update_core' );
		$plugin_updates = \get_site_transient( 'update_plugins' );
		$theme_updates  = \get_site_transient( 'update_themes' );

		$core_update_available = isset( $core_update->updates ) &&
			\is_array( $core_update->updates ) &&
			! empty( $core_update->updates );

		$plugin_update_count = isset( $plugin_updates->response ) && \is_array( $plugin_updates->response )
			? \count( $plugin_updates->response )
			: 0;

		$theme_update_count = isset( $theme_updates->response ) && \is_array( $theme_updates->response )
			? \count( $theme_updates->response )
			: 0;

		if ( ! \function_exists( 'get_plugins' ) ) {
			require_once \ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$disk_free     = \function_exists( 'disk_free_space' ) ? \disk_free_space( \ABSPATH ) : null;
		$disk_total    = \function_exists( 'disk_total_space' ) ? \disk_total_space( \ABSPATH ) : null;

		$data = [
			'php_version'          => \phpversion(),
			'wp_version'           => (string) $wp_version,
			'mysql_version'        => $wpdb->db_version(),
			'is_https'             => \is_ssl(),
			'debug_mode'           => \defined( 'WP_DEBUG' ) && \WP_DEBUG,
			'memory_limit'         => \ini_get( 'memory_limit' ),
			'max_upload_size'      => \wp_max_upload_size(),
			'php_max_execution'    => (int) \ini_get( 'max_execution_time' ),
			'multisite'            => \is_multisite(),
			'active_plugins_count' => \count( (array) \get_option( 'active_plugins', [] ) ),
			'core_update_available' => $core_update_available,
			'plugin_updates_count' => $plugin_update_count,
			'theme_updates_count'  => $theme_update_count,
			'disk_free_gb'         => \is_float( $disk_free ) ? \round( $disk_free / 1073741824, 2 ) : null,
			'disk_total_gb'        => \is_float( $disk_total ) ? \round( $disk_total / 1073741824, 2 ) : null,
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	/**
	 * Returns all scheduled WP-Cron tasks (tool: g2rd/get-cron-jobs).
	 *
	 * Requires manage_options capability.
	 *
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_get_cron_jobs( array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'manage_options' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		$cron_array = \_get_cron_array();
		$jobs       = [];

		if ( \is_array( $cron_array ) ) {
			foreach ( $cron_array as $timestamp => $hooks ) {
				if ( ! \is_array( $hooks ) ) {
					continue;
				}
				foreach ( $hooks as $hook => $callbacks ) {
					$schedule = '';
					if ( \is_array( $callbacks ) ) {
						$first = \reset( $callbacks );
						$schedule = \is_array( $first ) && isset( $first['schedule'] )
							? (string) $first['schedule']
							: '';
					}
					$jobs[] = [
						'hook'       => $hook,
						'next_run'   => \gmdate( 'Y-m-d H:i:s', (int) $timestamp ),
						'recurrence' => $schedule,
					];
				}
			}
		}

		\usort( $jobs, static fn( array $a, array $b ) => strcmp( $a['next_run'], $b['next_run'] ) );

		return $this->tool_success( (string) \wp_json_encode( [ 'jobs' => $jobs, 'total' => \count( $jobs ) ] ) );
	}

	/**
	 * Returns all navigation menus with their items (tool: g2rd/list-menus).
	 *
	 * @return array<string, mixed>
	 */
	private function exec_list_menus(): array {
		$menus = \wp_get_nav_menus();

		if ( \is_wp_error( $menus ) ) {
			return $this->tool_error( 'Failed to retrieve menus.' );
		}

		$result = [];
		if ( \is_array( $menus ) ) {
			foreach ( $menus as $menu ) {
				$items_raw   = \wp_get_nav_menu_items( $menu->term_id );
				$items       = [];

				if ( \is_array( $items_raw ) ) {
					foreach ( $items_raw as $item ) {
						$items[] = [
							'id'      => $item->ID,
							'title'   => $item->title,
							'url'     => $item->url,
							'type'    => $item->type,
							'order'   => (int) $item->menu_order,
							'parent'  => (int) $item->menu_item_parent,
						];
					}
				}

				$result[] = [
					'id'    => $menu->term_id,
					'name'  => $menu->name,
					'slug'  => $menu->slug,
					'count' => $menu->count,
					'items' => $items,
				];
			}
		}

		return $this->tool_success( (string) \wp_json_encode( [ 'menus' => $result, 'total' => \count( $result ) ] ) );
	}

	/**
	 * Describes the plugin-settings allowlist (tool: g2rd/list-plugin-settings).
	 *
	 * Introspection only: returns what may be written, never current values.
	 *
	 * @param array<string, mixed> $args        Tool arguments (active_only optional).
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_list_plugin_settings( array $args, array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'manage_options' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		$active_only = ! empty( $args['active_only'] );

		return $this->tool_success(
			(string) \wp_json_encode(
				[
					'plugins' => McpPluginSettings::describe( $active_only ),
					'note'    => 'Only these settings can be written. Anything else is refused by the allowlist.',
				]
			)
		);
	}

	/**
	 * Reads one allowlisted plugin setting (tool: g2rd/get-plugin-setting).
	 *
	 * @param array<string, mixed> $args        Tool arguments (plugin, setting).
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_get_plugin_setting( array $args, array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'manage_options' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		$plugin  = \sanitize_key( (string) ( $args['plugin'] ?? '' ) );
		$setting = \sanitize_key( (string) ( $args['setting'] ?? '' ) );

		$result = McpPluginSettings::read( $plugin, $setting );

		if ( ! $result['ok'] ) {
			return $this->tool_error( $result['error'] );
		}

		$definition = McpPluginSettings::get_definition( $plugin, $setting );

		return $this->tool_success(
			(string) \wp_json_encode(
				[
					'plugin'  => $plugin,
					'setting' => $setting,
					'label'   => $definition['label'] ?? '',
					'option'  => $definition['option'] ?? '',
					'value'   => $result['value'],
				]
			)
		);
	}

	/**
	 * Returns the post type slugs list-posts will accept.
	 *
	 * @return string[]
	 */
	private function readable_post_types(): array {
		$types = \get_post_types( [], 'objects' );
		$out   = [];

		foreach ( $types as $slug => $pto ) {
			if ( $pto->publicly_queryable || $pto->public ) {
				$out[] = (string) $slug;
			}
		}

		\sort( $out );

		return $out;
	}

	/**
	 * Lists registered post types (tool: g2rd/list-post-types).
	 *
	 * Exists so an agent never has to guess a slug: guessing is what produced an
	 * orphan post with a non-existent type in production.
	 *
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_list_post_types( array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'edit_posts' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		$rows = [];

		foreach ( \get_post_types( [], 'objects' ) as $slug => $pto ) {
			$rows[] = [
				'slug'               => (string) $slug,
				'label'              => isset( $pto->labels->name ) ? (string) $pto->labels->name : (string) $slug,
				'public'             => (bool) $pto->public,
				'publicly_queryable' => (bool) $pto->publicly_queryable,
				'show_in_rest'       => (bool) $pto->show_in_rest,
				'hierarchical'       => (bool) $pto->hierarchical,
				'readable_by_mcp'    => (bool) ( $pto->publicly_queryable || $pto->public ),
			];
		}

		return $this->tool_success(
			(string) \wp_json_encode(
				[
					'post_types' => $rows,
					'note'       => 'Only types with readable_by_mcp=true can be used with g2rd_list-posts. FluentCart products use "fluent-products" and must be created with g2rd_create-product, not g2rd_create-post.',
				]
			)
		);
	}

	/**
	 * Lists FluentCart products (tool: g2rd/list-products).
	 *
	 * @param array<string, mixed> $args        Tool arguments.
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_list_products( array $args, array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'edit_posts' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		$result = McpProducts::list_products( $args );

		if ( ! $result['ok'] ) {
			return $this->tool_error( $result['error'] );
		}

		return $this->tool_success( (string) \wp_json_encode( $result ) );
	}

	/**
	 * Returns one FluentCart product with its pricing (tool: g2rd/get-product).
	 *
	 * @param array<string, mixed> $args        Tool arguments.
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_get_product( array $args, array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'edit_posts' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		$result = McpProducts::get( \absint( $args['product_id'] ?? 0 ) );

		if ( ! $result['ok'] ) {
			return $this->tool_error( $result['error'] );
		}

		return $this->tool_success( (string) \wp_json_encode( $result['product'] ) );
	}

	/**
	 * Lists WooCommerce products (tool: g2rd/list-woo-products).
	 *
	 * @param array<string, mixed> $args        Tool arguments.
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_list_woo_products( array $args, array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'edit_posts' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		$result = McpWooProducts::list_products( $args );

		if ( ! $result['ok'] ) {
			return $this->tool_error( $result['error'] );
		}

		return $this->tool_success( (string) \wp_json_encode( $result ) );
	}

	/**
	 * Returns one WooCommerce product in full (tool: g2rd/get-woo-product).
	 *
	 * @param array<string, mixed> $args        Tool arguments.
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @return array<string, mixed>
	 */
	private function exec_get_woo_product( array $args, array $gate_result ): array {
		$cap_error = $this->check_admin_cap( $gate_result, 'edit_posts' );
		if ( null !== $cap_error ) {
			return $cap_error;
		}

		$result = McpWooProducts::get( \absint( $args['product_id'] ?? 0 ) );

		if ( ! $result['ok'] ) {
			return $this->tool_error( $result['error'] );
		}

		return $this->tool_success( (string) \wp_json_encode( $result['product'] ) );
	}

	// ── Write tool implementations ────────────────────────────────────────────

	/**
	 * Enqueues a write tool for human confirmation.
	 *
	 * Returns a pending acknowledgement rather than executing the operation.
	 * Actual execution happens in McpConfirmationQueue::confirm() after admin approval.
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
			$reason = $this->queue->get_last_error();

			// "Please retry" is wrong for a payload that is simply too large:
			// an identical retry fails identically. Report the real cause.
			return $this->tool_error(
				'' !== $reason ? $reason : 'Failed to enqueue operation. Please retry.'
			);
		}

		$data = [
			'status'     => 'pending',
			'message'    => 'An email has been sent to the administrator for confirmation. The operation will execute after approval.',
			'expires_at' => $result['expires_at'],
		];

		return $this->tool_success( (string) \wp_json_encode( $data ) );
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Detects the active SEO plugin by checking defined constants.
	 *
	 * Returns 'yoast', 'rank_math', 'seopress', 'aioseo', or 'none'.
	 *
	 * @return string
	 */
	private function detect_active_seo_plugin(): string {
		if ( \defined( 'WPSEO_VERSION' ) ) {
			return 'yoast';
		}
		if ( \defined( 'RANK_MATH_VERSION' ) ) {
			return 'rank_math';
		}
		if ( \defined( 'SEOPRESS_VERSION' ) ) {
			return 'seopress';
		}
		if ( \defined( 'AIOSEO_VERSION' ) ) {
			return 'aioseo';
		}
		return 'none';
	}

	/**
	 * Reads SEO meta fields for a post from the given SEO plugin.
	 *
	 * @param int    $post_id WordPress post ID.
	 * @param string $plugin  SEO plugin slug returned by detect_active_seo_plugin().
	 * @return array<string, mixed>
	 */
	private function get_seo_meta_for_post( int $post_id, string $plugin ): array {
		$meta = [
			'title'           => '',
			'description'     => '',
			'canonical'       => '',
			'noindex'         => false,
			'og_title'        => '',
			'og_description'  => '',
			'og_image'        => '',
			'focus_keyword'   => '',
		];

		switch ( $plugin ) {
			case 'yoast':
				$meta['title']          = (string) \get_post_meta( $post_id, '_yoast_wpseo_title', true );
				$meta['description']    = (string) \get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
				$meta['canonical']      = (string) \get_post_meta( $post_id, '_yoast_wpseo_canonical', true );
				$meta['noindex']        = '1' === (string) \get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
				$meta['focus_keyword']  = (string) \get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
				$meta['og_title']       = (string) \get_post_meta( $post_id, '_yoast_wpseo_opengraph-title', true );
				$meta['og_description'] = (string) \get_post_meta( $post_id, '_yoast_wpseo_opengraph-description', true );
				$og_img                 = \get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true );
				$meta['og_image']       = \is_string( $og_img ) ? $og_img : '';
				break;

			case 'rank_math':
				$meta['title']          = (string) \get_post_meta( $post_id, 'rank_math_title', true );
				$meta['description']    = (string) \get_post_meta( $post_id, 'rank_math_description', true );
				$meta['canonical']      = (string) \get_post_meta( $post_id, 'rank_math_canonical_url', true );
				$robots                 = (string) \get_post_meta( $post_id, 'rank_math_robots', true );
				$meta['noindex']        = false !== \strpos( $robots, 'noindex' );
				$meta['focus_keyword']  = (string) \get_post_meta( $post_id, 'rank_math_focus_keyword', true );
				$meta['og_title']       = (string) \get_post_meta( $post_id, 'rank_math_facebook_title', true );
				$meta['og_description'] = (string) \get_post_meta( $post_id, 'rank_math_facebook_description', true );
				$meta['og_image']       = (string) \get_post_meta( $post_id, 'rank_math_facebook_image', true );
				break;

			case 'seopress':
				$meta['title']          = (string) \get_post_meta( $post_id, '_seopress_titles_title', true );
				$meta['description']    = (string) \get_post_meta( $post_id, '_seopress_titles_desc', true );
				$meta['canonical']      = (string) \get_post_meta( $post_id, '_seopress_robots_canonical', true );
				$meta['noindex']        = '1' === (string) \get_post_meta( $post_id, '_seopress_robots_index', true );
				$meta['focus_keyword']  = (string) \get_post_meta( $post_id, '_seopress_analysis_target_kw', true );
				$meta['og_title']       = (string) \get_post_meta( $post_id, '_seopress_social_fb_title', true );
				$meta['og_description'] = (string) \get_post_meta( $post_id, '_seopress_social_fb_desc', true );
				$meta['og_image']       = (string) \get_post_meta( $post_id, '_seopress_social_fb_img', true );
				break;

			case 'aioseo':
				$meta['title']          = (string) \get_post_meta( $post_id, '_aioseo_title', true );
				$meta['description']    = (string) \get_post_meta( $post_id, '_aioseo_description', true );
				$meta['canonical']      = (string) \get_post_meta( $post_id, '_aioseo_canonical_url', true );
				$meta['noindex']        = '0' === (string) \get_post_meta( $post_id, '_aioseo_robots_default', true );
				$meta['og_title']       = (string) \get_post_meta( $post_id, '_aioseo_og_title', true );
				$meta['og_description'] = (string) \get_post_meta( $post_id, '_aioseo_og_description', true );
				$meta['og_image']       = (string) \get_post_meta( $post_id, '_aioseo_og_image_custom_url', true );
				$kp_json                = (string) \get_post_meta( $post_id, '_aioseo_keyphrases', true );
				$kp                     = $kp_json ? \json_decode( $kp_json, true ) : [];
				if ( \is_array( $kp ) && isset( $kp[0]['keyphrase'] ) ) {
					$meta['focus_keyword'] = (string) $kp[0]['keyphrase'];
				}
				break;
		}

		return $meta;
	}

	/**
	 * Checks that the authenticated user has the required WordPress capability.
	 *
	 * Returns a tool_error payload if the check fails, null if it passes.
	 *
	 * @param array<string, mixed> $gate_result Authorized gate result.
	 * @param string               $capability  Required WordPress capability.
	 * @return array<string, mixed>|null Tool error payload, or null on success.
	 */
	private function check_admin_cap( array $gate_result, string $capability ): ?array {
		$user_id = (int) ( $gate_result['user_id'] ?? 0 );
		$user    = $user_id > 0 ? \get_userdata( $user_id ) : false;

		if ( ! $user instanceof \WP_User || ! $user->has_cap( $capability ) ) {
			return $this->tool_error( "Insufficient permissions. Requires: {$capability}" );
		}

		return null;
	}

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
