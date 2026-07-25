<?php
/**
 * MCP Plugin Settings — allowlisted plugin configuration layer
 *
 * Generic authorization + read/write engine for third-party plugin settings
 * exposed over MCP. The engine itself is plugin-agnostic: everything it is
 * allowed to touch comes from the hard-coded REGISTRY constant below.
 *
 * Design rules (do not relax):
 *
 *   1. Allowlist only — a setting absent from REGISTRY is refused. There is no
 *      code path that writes an arbitrary option key or an arbitrary sub-key.
 *   2. Secrets are never readable nor writable — see SECRET_PATTERNS. This is
 *      defence in depth on top of the allowlist, not the primary control.
 *   3. Array-backed options are read-modify-written: only the targeted
 *      sub-key changes, every sibling key is preserved byte for byte.
 *   4. Sanitization is declared per setting, never inferred. Plugins disagree
 *      on how they store booleans ('1'/unset for SEOPress, real bool for
 *      Yoast, 'yes'/'no' for WooCommerce) and guessing corrupts settings.
 *
 * Storage models supported:
 *
 *   scalar — the option value IS the setting        (WooCommerce)
 *   array  — PHP serialized array, sub-key path     (SEOPress, Yoast)
 *   json   — JSON-encoded string, sub-key path      (All in One SEO)
 *
 * Every option key, sub-key path and value format in REGISTRY was read from
 * the plugin sources (SEOPress 10.0.2, Yoast SEO, WooCommerce), never guessed.
 *
 * @package    G2RD
 * @since      1.27.0
 * @license    EUPL-1.2
 * @copyright  (c) 2026 Sebastien GERARD
 */

namespace G2RD;

/**
 * Allowlisted read/write access to third-party plugin settings.
 */
class McpPluginSettings {

	/** Storage model: the option value is the setting itself. */
	public const STORAGE_SCALAR = 'scalar';

	/** Storage model: option holds a PHP array, setting lives at a sub-key path. */
	public const STORAGE_ARRAY = 'array';

	/** Storage model: option holds a JSON string, setting lives at a sub-key path. */
	public const STORAGE_JSON = 'json';

	/**
	 * Substrings that must never appear in an allowlisted option or path.
	 *
	 * Defence in depth: even if a future contributor adds a REGISTRY entry
	 * pointing at credentials, validate_definition() refuses to serve it.
	 *
	 * @var string[]
	 */
	private const SECRET_PATTERNS = [
		'passw',
		'pwd',
		'secret',
		'token',
		'api_key',
		'apikey',
		'_key',
		'private',
		'salt',
		'nonce',
		'licen',
		'credential',
		'auth',
	];

	/**
	 * Hard-coded allowlist: plugin => settings => definition.
	 *
	 * Plugin entries:
	 *   label     — human-readable plugin name.
	 *   detect    — ['constant' => string] or ['class' => string] activity probe.
	 *   settings  — setting slug => definition.
	 *
	 * Setting definitions:
	 *   label        — human-readable description.
	 *   option       — WordPress option name.
	 *   storage      — one of STORAGE_*.
	 *   path         — sub-key path inside the option (empty for scalar).
	 *   type         — boolean | text | enum | post_types_map.
	 *   bool_format  — one_or_unset | native | yes_no (type=boolean only).
	 *   values       — allowed values (type=enum only).
	 *   map_key      — leaf key written under each post type (post_types_map only).
	 *   requires     — extra probe the setting needs on top of the plugin itself,
	 *                  e.g. a paid add-on that owns the option. Without it a write
	 *                  would report success while the plugin ignores the value.
	 *   side_effects — subset of ['flush_rewrite', 'flush_cache'].
	 *   verify_path  — site-relative URL worth checking after the write.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private const REGISTRY = [

		// ── SEOPress ──────────────────────────────────────────────────────────
		'seopress'    => [
			'label'    => 'SEOPress',
			'detect'   => [ 'constant' => 'SEOPRESS_VERSION' ],
			'settings' => [
				'news_sitemap_enabled'  => [
					'label'        => 'Enable the Google News XML sitemap.',
					'option'       => 'seopress_pro_option_name',
					'storage'      => self::STORAGE_ARRAY,
					'path'         => [ 'seopress_news_enable' ],
					'type'         => 'boolean',
					'bool_format'  => 'one_or_unset',
					'requires'     => [ 'constant' => 'SEOPRESS_PRO_VERSION' ],
					'side_effects' => [ 'flush_rewrite', 'flush_cache' ],
					'verify_path'  => '/news.xml',
				],
				'news_publication_name' => [
					'label'        => 'Google News publication name.',
					'option'       => 'seopress_pro_option_name',
					'storage'      => self::STORAGE_ARRAY,
					'path'         => [ 'seopress_news_name' ],
					'type'         => 'text',
					'requires'     => [ 'constant' => 'SEOPRESS_PRO_VERSION' ],
					'side_effects' => [ 'flush_cache' ],
					'verify_path'  => '/news.xml',
				],
				'news_post_types'       => [
					'label'        => 'Post types included in the Google News sitemap.',
					'option'       => 'seopress_pro_option_name',
					'storage'      => self::STORAGE_ARRAY,
					'path'         => [ 'seopress_news_name_post_types_list' ],
					'type'         => 'post_types_map',
					'map_key'      => 'include',
					'requires'     => [ 'constant' => 'SEOPRESS_PRO_VERSION' ],
					'side_effects' => [ 'flush_rewrite', 'flush_cache' ],
					'verify_path'  => '/news.xml',
				],
			],
		],

		// ── Yoast SEO ─────────────────────────────────────────────────────────
		'yoast'       => [
			'label'    => 'Yoast SEO',
			'detect'   => [ 'constant' => 'WPSEO_VERSION' ],
			'settings' => [
				'xml_sitemap_enabled' => [
					'label'        => 'Enable XML sitemaps.',
					'option'       => 'wpseo',
					'storage'      => self::STORAGE_ARRAY,
					'path'         => [ 'enable_xml_sitemap' ],
					'type'         => 'boolean',
					'bool_format'  => 'native',
					'side_effects' => [ 'flush_rewrite', 'flush_cache' ],
					'verify_path'  => '/sitemap_index.xml',
				],
				'title_separator'     => [
					'label'        => 'Title separator character.',
					'option'       => 'wpseo_titles',
					'storage'      => self::STORAGE_ARRAY,
					'path'         => [ 'separator' ],
					'type'         => 'enum',
					'values'       => [
						'sc-dash',
						'sc-ndash',
						'sc-mdash',
						'sc-colon',
						'sc-middot',
						'sc-bull',
						'sc-star',
						'sc-smstar',
						'sc-pipe',
						'sc-tilde',
						'sc-laquo',
						'sc-raquo',
						'sc-lt',
						'sc-gt',
					],
					'side_effects' => [ 'flush_cache' ],
				],
			],
		],

		// ── WooCommerce ───────────────────────────────────────────────────────
		'woocommerce' => [
			'label'    => 'WooCommerce',
			'detect'   => [ 'class' => 'WooCommerce' ],
			'settings' => [
				'enable_reviews' => [
					'label'        => 'Enable product reviews.',
					'option'       => 'woocommerce_enable_reviews',
					'storage'      => self::STORAGE_SCALAR,
					'path'         => [],
					'type'         => 'boolean',
					'bool_format'  => 'yes_no',
					'side_effects' => [ 'flush_cache' ],
				],
				'enable_coupons' => [
					'label'        => 'Enable coupon codes at checkout.',
					'option'       => 'woocommerce_enable_coupons',
					'storage'      => self::STORAGE_SCALAR,
					'path'         => [],
					'type'         => 'boolean',
					'bool_format'  => 'yes_no',
					'side_effects' => [ 'flush_cache' ],
				],
				'manage_stock'   => [
					'label'        => 'Enable stock management.',
					'option'       => 'woocommerce_manage_stock',
					'storage'      => self::STORAGE_SCALAR,
					'path'         => [],
					'type'         => 'boolean',
					'bool_format'  => 'yes_no',
					'side_effects' => [ 'flush_cache' ],
				],
			],
		],
	];

	/**
	 * Returns the list of plugin slugs known to the allowlist.
	 *
	 * @return string[]
	 */
	public static function plugin_slugs(): array {
		return \array_keys( self::REGISTRY );
	}

	/**
	 * Returns every allowlisted setting slug, across all plugins.
	 *
	 * Used to build the tool input schema enum. Slugs are unique per plugin,
	 * so the union is de-duplicated.
	 *
	 * @return string[]
	 */
	public static function setting_slugs(): array {
		$slugs = [];

		foreach ( self::REGISTRY as $plugin ) {
			foreach ( \array_keys( $plugin['settings'] ) as $slug ) {
				$slugs[ $slug ] = true;
			}
		}

		return \array_keys( $slugs );
	}

	/**
	 * Reports whether a plugin from the allowlist is currently active.
	 *
	 * @param string $plugin Plugin slug.
	 * @return bool True when the plugin's probe resolves.
	 */
	public static function is_plugin_active( string $plugin ): bool {
		$entry = self::REGISTRY[ $plugin ] ?? null;

		return null !== $entry && self::probe( (array) ( $entry['detect'] ?? [] ) );
	}

	/**
	 * Reports whether a setting's extra requirement is met.
	 *
	 * Some settings live in an option owned by a paid add-on. Writing them on a
	 * site without that add-on succeeds at the database level while the plugin
	 * ignores the value entirely — a silent no-op the caller must not see as
	 * success. SEOPress is the case in point: the Google News settings live in
	 * `seopress_pro_option_name`, but the free plugin also defines
	 * SEOPRESS_VERSION, so only SEOPRESS_PRO_VERSION proves the tier.
	 *
	 * @param array<string, mixed> $definition Setting definition.
	 * @return bool True when the setting can actually take effect.
	 */
	private static function requirement_met( array $definition ): bool {
		if ( empty( $definition['requires'] ) ) {
			return true;
		}

		return self::probe( (array) $definition['requires'] );
	}

	/**
	 * Resolves a constant/class presence probe.
	 *
	 * @param array<string, string> $probe Probe descriptor.
	 * @return bool True when the probe resolves.
	 */
	private static function probe( array $probe ): bool {
		if ( isset( $probe['constant'] ) ) {
			return \defined( $probe['constant'] );
		}

		if ( isset( $probe['class'] ) ) {
			return \class_exists( $probe['class'] );
		}

		return false;
	}

	/**
	 * Returns a validated setting definition, or null when not allowlisted.
	 *
	 * @param string $plugin  Plugin slug.
	 * @param string $setting Setting slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_definition( string $plugin, string $setting ): ?array {
		$definition = self::REGISTRY[ $plugin ]['settings'][ $setting ] ?? null;

		if ( null === $definition || ! self::validate_definition( $definition ) ) {
			return null;
		}

		return $definition;
	}

	/**
	 * Rejects any definition that points at credential-like data.
	 *
	 * @param array<string, mixed> $definition Setting definition.
	 * @return bool True when the definition is safe to serve.
	 */
	private static function validate_definition( array $definition ): bool {
		$haystack = \strtolower(
			(string) ( $definition['option'] ?? '' ) . '|' . \implode( '|', (array) ( $definition['path'] ?? [] ) )
		);

		foreach ( self::SECRET_PATTERNS as $pattern ) {
			if ( false !== \strpos( $haystack, $pattern ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Describes the allowlist for introspection (tool: g2rd/list-plugin-settings).
	 *
	 * Never exposes current values — that is get-plugin-setting's job, and it
	 * runs its own capability check.
	 *
	 * @param bool $active_only When true, omit plugins that are not installed.
	 * @return array<int, array<string, mixed>>
	 */
	public static function describe( bool $active_only = false ): array {
		$out = [];

		foreach ( self::REGISTRY as $slug => $entry ) {
			$is_active = self::is_plugin_active( $slug );

			if ( $active_only && ! $is_active ) {
				continue;
			}

			$settings = [];

			foreach ( $entry['settings'] as $setting_slug => $definition ) {
				if ( ! self::validate_definition( $definition ) ) {
					continue;
				}

				$described = [
					'setting'     => $setting_slug,
					'label'       => $definition['label'],
					'type'        => $definition['type'],
					'option'      => $definition['option'],
					'storage'     => $definition['storage'],
					'path'        => $definition['path'],
					'verify_path' => $definition['verify_path'] ?? null,
					// False when the setting needs an add-on the site lacks: writing
					// it would report success while the plugin ignores the value.
					'available'   => $is_active && self::requirement_met( $definition ),
				];

				if ( isset( $definition['values'] ) ) {
					$described['allowed_values'] = $definition['values'];
				}

				if ( 'boolean' === $definition['type'] ) {
					$described['allowed_values'] = [ true, false ];
				}

				if ( 'post_types_map' === $definition['type'] ) {
					// Must stay in sync with sanitize(), which intersects against
					// this very same set.
					$described['allowed_values'] = \array_values( \get_post_types( [ 'public' => true ] ) );
				}

				$settings[] = $described;
			}

			$out[] = [
				'plugin'   => $slug,
				'label'    => $entry['label'],
				'active'   => $is_active,
				'settings' => $settings,
			];
		}

		return $out;
	}

	/**
	 * Reads the current value of an allowlisted setting.
	 *
	 * @param string $plugin  Plugin slug.
	 * @param string $setting Setting slug.
	 * @return array{ok: bool, value?: mixed, error?: string}
	 */
	public static function read( string $plugin, string $setting ): array {
		$definition = self::get_definition( $plugin, $setting );

		if ( null === $definition ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Setting "%s" is not allowlisted for plugin "%s".', $setting, $plugin ),
			];
		}

		if ( ! self::is_plugin_active( $plugin ) ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Plugin "%s" is not active on this site.', $plugin ),
			];
		}

		if ( ! self::requirement_met( $definition ) ) {
			return [
				'ok'    => false,
				'error' => \sprintf(
					'Setting "%s" needs an add-on that is not installed. Writing it would silently have no effect.',
					$setting
				),
			];
		}

		return [
			'ok'    => true,
			'value' => self::current_value( $definition ),
		];
	}

	/**
	 * Resolves the stored value of a setting, normalised for output.
	 *
	 * @param array<string, mixed> $definition Setting definition.
	 * @return mixed Current value (null when unset).
	 */
	public static function current_value( array $definition ) {
		$container = self::load_container( $definition );

		if ( self::STORAGE_SCALAR === $definition['storage'] ) {
			$raw = $container;
		} else {
			$raw = self::dig( \is_array( $container ) ? $container : [], (array) $definition['path'] );
		}

		switch ( $definition['type'] ) {
			case 'boolean':
				return self::to_bool( $raw, (string) $definition['bool_format'] );

			case 'post_types_map':
				if ( ! \is_array( $raw ) ) {
					return [];
				}

				$map_key  = (string) $definition['map_key'];
				$included = [];

				foreach ( $raw as $post_type => $flags ) {
					if ( \is_array( $flags ) && ! empty( $flags[ $map_key ] ) ) {
						$included[] = (string) $post_type;
					}
				}

				return $included;

			default:
				return null === $raw ? '' : $raw;
		}
	}

	/**
	 * Validates and sanitizes an incoming value against its definition.
	 *
	 * @param array<string, mixed> $definition Setting definition.
	 * @param mixed                $value      Raw value from the MCP client.
	 * @return array{ok: bool, value?: mixed, error?: string}
	 */
	public static function sanitize( array $definition, $value ): array {
		switch ( $definition['type'] ) {

			case 'boolean':
				if ( \is_string( $value ) ) {
					$lower = \strtolower( \trim( $value ) );
					$value = \in_array( $lower, [ '1', 'true', 'yes', 'on' ], true );
				}

				return [
					'ok'    => true,
					'value' => (bool) $value,
				];

			case 'text':
				if ( ! \is_scalar( $value ) ) {
					return [
						'ok'    => false,
						'error' => 'Expected a string value.',
					];
				}

				return [
					'ok'    => true,
					'value' => \sanitize_text_field( (string) $value ),
				];

			case 'enum':
				$candidate = \is_scalar( $value ) ? (string) $value : '';

				if ( ! \in_array( $candidate, (array) $definition['values'], true ) ) {
					return [
						'ok'    => false,
						'error' => \sprintf(
							'Value "%s" is not allowed. Allowed values: %s.',
							$candidate,
							\implode( ', ', (array) $definition['values'] )
						),
					];
				}

				return [
					'ok'    => true,
					'value' => $candidate,
				];

			case 'post_types_map':
				if ( \is_string( $value ) ) {
					$value = \array_map( 'trim', \explode( ',', $value ) );
				}

				if ( ! \is_array( $value ) ) {
					return [
						'ok'    => false,
						'error' => 'Expected an array of post type slugs.',
					];
				}

				// Public types only, matching exactly what describe() advertises.
				// Accepting internal types (revision, nav_menu_item…) would let a
				// client write entries the introspection never offered.
				$known    = \array_values( \get_post_types( [ 'public' => true ] ) );
				$supplied = \array_map( 'sanitize_key', \array_map( 'strval', $value ) );
				$supplied = \array_values( \array_filter( $supplied, static fn( string $slug ): bool => '' !== $slug ) );
				$filtered = \array_values( \array_intersect( $supplied, $known ) );

				// An empty submission is legitimate: it clears the list, and the
				// rollback path needs it to restore a previously empty setting.
				if ( [] === $filtered && [] !== $supplied ) {
					return [
						'ok'    => false,
						'error' => \sprintf(
							'No valid public post type in the submitted list. Allowed: %s.',
							\implode( ', ', $known )
						),
					];
				}

				\sort( $filtered );

				return [
					'ok'    => true,
					'value' => $filtered,
				];

			default:
				return [
					'ok'    => false,
					'error' => 'Unsupported setting type.',
				];
		}
	}

	/**
	 * Writes an allowlisted setting, preserving every sibling key.
	 *
	 * Returns the old and new values so the caller can log them and offer a
	 * rollback. Performs no capability check — the caller is responsible for
	 * that, and McpConfirmationQueue does it inside the switched user context.
	 *
	 * @param string $plugin  Plugin slug.
	 * @param string $setting Setting slug.
	 * @param mixed  $value   Sanitized value.
	 * @return array{ok: bool, error?: string, option?: string, path?: array, old?: mixed, new?: mixed, side_effects?: array, verify_path?: ?string}
	 */
	public static function write( string $plugin, string $setting, $value ): array {
		$definition = self::get_definition( $plugin, $setting );

		if ( null === $definition ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Setting "%s" is not allowlisted for plugin "%s".', $setting, $plugin ),
			];
		}

		if ( ! self::is_plugin_active( $plugin ) ) {
			return [
				'ok'    => false,
				'error' => \sprintf( 'Plugin "%s" is not active on this site.', $plugin ),
			];
		}

		if ( ! self::requirement_met( $definition ) ) {
			return [
				'ok'    => false,
				'error' => \sprintf(
					'Setting "%s" needs an add-on that is not installed. Writing it would silently have no effect.',
					$setting
				),
			];
		}

		$clean = self::sanitize( $definition, $value );

		if ( ! $clean['ok'] ) {
			return [
				'ok'    => false,
				'error' => $clean['error'],
			];
		}

		$old_value = self::current_value( $definition );

		if ( self::STORAGE_SCALAR === $definition['storage'] ) {
			$stored = self::encode_leaf( $definition, $clean['value'] );
			$saved  = \update_option( $definition['option'], $stored );
		} else {
			$saved = self::write_into_container( $definition, $clean['value'] );
		}

		if ( ! $saved ) {
			// update_option() returns false when the stored value is unchanged,
			// which is a success here. Lists are compared as sets: current_value()
			// rebuilds them in map-key order while the client keeps submission
			// order, so a strict === would report a spurious failure.
			if ( ! self::same_value( self::current_value( $definition ), $clean['value'] ) ) {
				return [
					'ok'    => false,
					'error' => 'WordPress refused to persist the option.',
				];
			}
		}

		return [
			'ok'           => true,
			'option'       => $definition['option'],
			'path'         => $definition['path'],
			'old'          => $old_value,
			'new'          => self::current_value( $definition ),
			'side_effects' => (array) ( $definition['side_effects'] ?? [] ),
			'verify_path'  => $definition['verify_path'] ?? null,
		];
	}

	/**
	 * Restores a previously captured value (rollback path).
	 *
	 * Returns the same payload as write() — including `side_effects` — because a
	 * rollback needs the very flushes the original write triggered. Reverting a
	 * sitemap toggle without flushing rewrite rules leaves /news.xml answering
	 * for a sitemap that no longer exists.
	 *
	 * @param string $plugin  Plugin slug.
	 * @param string $setting Setting slug.
	 * @param mixed  $value   Value captured before the write.
	 * @return array{ok: bool, error?: string, option?: string, path?: array, old?: mixed, new?: mixed, side_effects?: array, verify_path?: ?string}
	 */
	public static function restore( string $plugin, string $setting, $value ): array {
		return self::write( $plugin, $setting, $value );
	}

	/**
	 * Compares two stored values, treating lists as unordered sets.
	 *
	 * @param mixed $a First value.
	 * @param mixed $b Second value.
	 * @return bool True when both represent the same setting value.
	 */
	private static function same_value( $a, $b ): bool {
		if ( \is_array( $a ) && \is_array( $b ) ) {
			\sort( $a );
			\sort( $b );
		}

		return $a === $b;
	}

	// ── Internals ─────────────────────────────────────────────────────────────

	/**
	 * Loads the option container, decoded according to the storage model.
	 *
	 * @param array<string, mixed> $definition Setting definition.
	 * @return mixed Array for array/json storage, raw value for scalar.
	 */
	private static function load_container( array $definition ) {
		$raw = \get_option( $definition['option'] );

		switch ( $definition['storage'] ) {
			case self::STORAGE_JSON:
				$decoded = \is_string( $raw ) ? \json_decode( $raw, true ) : null;

				return \is_array( $decoded ) ? $decoded : [];

			case self::STORAGE_ARRAY:
				return \is_array( $raw ) ? $raw : [];

			default:
				return $raw;
		}
	}

	/**
	 * Persists a container back to the option, re-encoding for JSON storage.
	 *
	 * @param array<string, mixed> $definition Setting definition.
	 * @param array<string, mixed> $container  Full container to store.
	 * @return bool True when update_option reports a change.
	 */
	private static function save_container( array $definition, array $container ): bool {
		if ( self::STORAGE_JSON === $definition['storage'] ) {
			return \update_option( $definition['option'], (string) \wp_json_encode( $container ) );
		}

		return \update_option( $definition['option'], $container );
	}

	/**
	 * Read-modify-writes a single sub-key, leaving all siblings untouched.
	 *
	 * @param array<string, mixed> $definition Setting definition.
	 * @param mixed                $value      Sanitized value.
	 * @return bool True on success.
	 */
	private static function write_into_container( array $definition, $value ): bool {
		$container = self::load_container( $definition );
		$container = \is_array( $container ) ? $container : [];
		$path      = (array) $definition['path'];

		if ( 'post_types_map' === $definition['type'] ) {
			$map_key = (string) $definition['map_key'];
			$current = self::dig( $container, $path );
			$current = \is_array( $current ) ? $current : [];

			// Clear the leaf flag everywhere, then set it only on the requested types.
			foreach ( $current as $post_type => $flags ) {
				if ( \is_array( $flags ) ) {
					unset( $current[ $post_type ][ $map_key ] );
				}
			}

			foreach ( (array) $value as $post_type ) {
				$current[ $post_type ][ $map_key ] = '1';
			}

			$container = self::plant( $container, $path, $current );

			return self::save_container( $definition, $container );
		}

		if ( 'boolean' === $definition['type'] && 'one_or_unset' === ( $definition['bool_format'] ?? '' ) ) {
			// SEOPress stores "off" by removing the key entirely.
			$container = $value
				? self::plant( $container, $path, '1' )
				: self::uproot( $container, $path );

			return self::save_container( $definition, $container );
		}

		$container = self::plant( $container, $path, self::encode_leaf( $definition, $value ) );

		return self::save_container( $definition, $container );
	}

	/**
	 * Converts a sanitized value into its stored representation.
	 *
	 * @param array<string, mixed> $definition Setting definition.
	 * @param mixed                $value      Sanitized value.
	 * @return mixed Stored representation.
	 */
	private static function encode_leaf( array $definition, $value ) {
		if ( 'boolean' !== $definition['type'] ) {
			return $value;
		}

		switch ( $definition['bool_format'] ?? 'native' ) {
			case 'yes_no':
				return $value ? 'yes' : 'no';

			case 'one_or_unset':
				return $value ? '1' : '';

			default:
				return (bool) $value;
		}
	}

	/**
	 * Normalises a stored value into a real boolean.
	 *
	 * @param mixed  $raw    Stored value.
	 * @param string $format Boolean storage format.
	 * @return bool
	 */
	private static function to_bool( $raw, string $format ): bool {
		if ( 'yes_no' === $format ) {
			return 'yes' === $raw;
		}

		if ( 'one_or_unset' === $format ) {
			return '1' === (string) $raw;
		}

		return (bool) $raw;
	}

	/**
	 * Reads a nested value by path.
	 *
	 * @param array<string, mixed> $container Container array.
	 * @param string[]             $path      Sub-key path.
	 * @return mixed Value, or null when the path does not resolve.
	 */
	private static function dig( array $container, array $path ) {
		$cursor = $container;

		foreach ( $path as $key ) {
			if ( ! \is_array( $cursor ) || ! \array_key_exists( $key, $cursor ) ) {
				return null;
			}

			$cursor = $cursor[ $key ];
		}

		return $cursor;
	}

	/**
	 * Writes a nested value by path, creating intermediate levels as needed.
	 *
	 * @param array<string, mixed> $container Container array.
	 * @param string[]             $path      Sub-key path.
	 * @param mixed                $value     Value to set.
	 * @return array<string, mixed> Updated container.
	 */
	private static function plant( array $container, array $path, $value ): array {
		if ( [] === $path ) {
			return $container;
		}

		$cursor = &$container;

		foreach ( $path as $key ) {
			if ( ! isset( $cursor[ $key ] ) || ! \is_array( $cursor[ $key ] ) ) {
				$cursor[ $key ] = [];
			}

			$cursor = &$cursor[ $key ];
		}

		$cursor = $value;
		unset( $cursor );

		return $container;
	}

	/**
	 * Removes a nested key by path, leaving siblings intact.
	 *
	 * @param array<string, mixed> $container Container array.
	 * @param string[]             $path      Sub-key path.
	 * @return array<string, mixed> Updated container.
	 */
	private static function uproot( array $container, array $path ): array {
		if ( [] === $path ) {
			return $container;
		}

		$leaf   = \array_pop( $path );
		$cursor = &$container;

		foreach ( $path as $key ) {
			if ( ! isset( $cursor[ $key ] ) || ! \is_array( $cursor[ $key ] ) ) {
				return $container;
			}

			$cursor = &$cursor[ $key ];
		}

		unset( $cursor[ $leaf ] );
		unset( $cursor );

		return $container;
	}
}
