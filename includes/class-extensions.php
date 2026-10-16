<?php
/**
 * Extension registry: descriptor discovery, enabled-state resolution and booting.
 *
 * Extensions are small quality-of-life features that are not blocks — an admin
 * column, a post type rename, a plugin integration. They ship no JS bundle and
 * no block.json, so they are discovered from `extensions/` rather than `build/`.
 *
 * The static methods above the "WordPress adapters" marker are pure — no WP
 * calls — so they can be exercised by tools/check.php.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary;

use IsuDevLibrary\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Discovers extension descriptors, resolves their state and boots the enabled ones.
 */
class Extensions {

	/**
	 * Option holding panel-managed toggles, as slug => bool.
	 *
	 * @var string
	 */
	const OPTION = 'isudev_library_extensions';

	/**
	 * Key under the `library` subtree of isudev.json that pins extensions in code.
	 *
	 * Extensions live under their own key rather than next to blocks because
	 * blocks are keyed by full block name (`isudev/site-header`) and an extension
	 * has no block name to key on.
	 *
	 * @var string
	 */
	const CONFIG_KEY = 'extensions';

	/**
	 * Option holding panel-managed extension settings, as slug => key => value.
	 *
	 * @var string
	 */
	const SETTINGS_OPTION = 'isudev_library_extension_settings';

	/**
	 * Field types an extension can declare in its descriptor's `settings`.
	 *
	 * @var string[]
	 */
	const SETTING_TYPES = array( 'boolean', 'text', 'email', 'post_types' );

	/**
	 * Known categories, in the order the admin panel shows them. Pure.
	 *
	 * Adding a category here is all that is needed for a descriptor to declare
	 * it; an unknown category still renders, under its own raw slug.
	 *
	 * @return array slug => human-readable label.
	 */
	public static function categories(): array {
		return array(
			'admin'         => \__( 'Admin experience', 'isudev-library' ),
			'content'       => \__( 'Content', 'isudev-library' ),
			'editor'        => \__( 'Block editor', 'isudev-library' ),
			'blocks'        => \__( 'Core blocks', 'isudev-library' ),
			'security'      => \__( 'Security', 'isudev-library' ),
			'accessibility' => \__( 'Accessibility', 'isudev-library' ),
			'theme'         => \__( 'Theme', 'isudev-library' ),
			'plugins'       => \__( 'Plugin integrations', 'isudev-library' ),
		);
	}

	/**
	 * Normalize a raw descriptor, filling every key. Pure.
	 *
	 * @param array $raw Descriptor as returned by an extension's extension.php.
	 * @return array|null Normalized descriptor, or null when invalid.
	 */
	public static function normalize_descriptor( array $raw ): ?array {
		$slug  = $raw['slug'] ?? null;
		$title = $raw['title'] ?? null;

		if ( ! \is_string( $slug ) || '' === $slug || ! \is_string( $title ) || '' === $title ) {
			return null;
		}

		$category = $raw['category'] ?? '';
		$boot     = $raw['boot'] ?? '';

		return array(
			'slug'        => $slug,
			'title'       => $title,
			'description' => \is_string( $raw['description'] ?? null ) ? $raw['description'] : '',
			'category'    => \is_string( $category ) && '' !== $category ? $category : 'admin',
			'default'     => (bool) ( $raw['default'] ?? false ),
			'bootstrap'   => self::string_list( $raw['bootstrap'] ?? array() ),
			'boot'        => \is_string( $boot ) ? $boot : '',
			'requires'    => self::normalize_requirement( $raw['requires'] ?? null ),
			'settings'    => self::normalize_settings( $raw['settings'] ?? array() ),
		);
	}

	/**
	 * Normalize the optional `settings` field list of a descriptor. Pure.
	 *
	 * Each field is `key` (camelCase), `type` (see SETTING_TYPES), `label`,
	 * optional `help`, optional `default`, and for `post_types` an optional
	 * `supports` feature the listed post types must have. Invalid fields are
	 * dropped; a duplicate key keeps the first.
	 *
	 * @param mixed $value Raw `settings` entry.
	 * @return array List of normalized fields.
	 */
	public static function normalize_settings( $value ): array {
		if ( ! \is_array( $value ) ) {
			return array();
		}

		$fields = array();

		foreach ( $value as $field ) {
			if ( ! \is_array( $field ) ) {
				continue;
			}

			$key   = $field['key'] ?? '';
			$type  = $field['type'] ?? '';
			$label = $field['label'] ?? '';

			if ( ! \is_string( $key ) || 1 !== \preg_match( '/^[a-z][a-zA-Z0-9]{0,39}$/', $key ) || isset( $fields[ $key ] )
				|| ! \in_array( $type, self::SETTING_TYPES, true ) || ! \is_string( $label ) || '' === $label ) {
				continue;
			}

			$supports = $field['supports'] ?? '';

			$fields[ $key ] = array(
				'key'      => $key,
				'type'     => $type,
				'label'    => $label,
				'help'     => \is_string( $field['help'] ?? null ) ? $field['help'] : '',
				'default'  => self::sanitize_setting_value( array( 'type' => $type ), $field['default'] ?? null ),
				'supports' => 'post_types' === $type && \is_string( $supports ) ? $supports : '',
			);
		}

		return \array_values( $fields );
	}

	/**
	 * Coerce a value to a field's type. Pure.
	 *
	 * `email` is only trimmed here; the WordPress adapter validates it with
	 * is_email() before storing.
	 *
	 * @param array $field Normalized field (only `type` is read).
	 * @param mixed $value Candidate value.
	 * @return bool|string|array
	 */
	public static function sanitize_setting_value( array $field, $value ) {
		switch ( $field['type'] ?? '' ) {
			case 'boolean':
				return (bool) $value;

			case 'post_types':
				$slugs = array();
				foreach ( \is_array( $value ) ? $value : array() as $slug ) {
					if ( \is_string( $slug ) && 1 === \preg_match( '/^[a-z0-9_-]{1,20}$/', $slug ) ) {
						$slugs[] = $slug;
					}
				}
				return \array_values( \array_unique( $slugs ) );

			default:
				$text = \is_scalar( $value ) ? \trim( (string) $value ) : '';
				$text = (string) \preg_replace( '/[\x00-\x1F\x7F<>]/', '', $text );

				return \function_exists( 'mb_substr' ) ? \mb_substr( $text, 0, 200 ) : \substr( $text, 0, 200 );
		}
	}

	/**
	 * Normalize the optional third-party requirement of a descriptor. Pure.
	 *
	 * `label` is what the panel shows the operator, so it is the one key that
	 * makes a requirement worth declaring at all — a requirement without it is
	 * dropped rather than rendered as an unexplained lock.
	 *
	 * @param mixed $value Raw `requires` entry.
	 * @return array|null array{label:string,class:string,function:string}, or null.
	 */
	public static function normalize_requirement( $value ): ?array {
		if ( ! \is_array( $value ) ) {
			return null;
		}

		$label = $value['label'] ?? '';

		if ( ! \is_string( $label ) || '' === $label ) {
			return null;
		}

		return array(
			'label'    => $label,
			'class'    => \is_string( $value['class'] ?? null ) ? $value['class'] : '',
			'function' => \is_string( $value['function'] ?? null ) ? $value['function'] : '',
		);
	}

	/**
	 * Whether a normalized requirement is satisfied in this PHP process. Pure.
	 *
	 * Only `class_exists()` and `function_exists()` — plain PHP, no WordPress —
	 * so this stays testable. A requirement naming neither is treated as met:
	 * the label alone documents a dependency this plugin cannot detect.
	 *
	 * @param array|null $requires Normalized requirement, or null when absent.
	 * @return bool
	 */
	public static function requirement_met( ?array $requires ): bool {
		if ( null === $requires ) {
			return true;
		}

		if ( '' !== $requires['class'] ) {
			return \class_exists( $requires['class'] );
		}

		if ( '' !== $requires['function'] ) {
			return \function_exists( $requires['function'] );
		}

		return true;
	}

	/**
	 * Coerce a value into a list of non-empty strings. Pure.
	 *
	 * @param mixed $value Candidate list.
	 * @return array List of non-empty strings.
	 */
	private static function string_list( $value ): array {
		if ( ! \is_array( $value ) ) {
			return array();
		}

		$out = array();
		foreach ( $value as $item ) {
			if ( \is_string( $item ) && '' !== $item ) {
				$out[] = $item;
			}
		}

		return $out;
	}

	/**
	 * Resolve enabled state for every descriptor. Pure.
	 *
	 * Precedence: an unmet third-party requirement, then isudev.json, then the
	 * panel option, then the descriptor's own default.
	 *
	 * Unlike blocks, extensions default to OFF unless a descriptor says
	 * otherwise. A block only appears when an editor inserts it; an extension
	 * changes the admin or the front end the moment it loads, so switching one
	 * on is the operator's decision to make.
	 *
	 * @param array $descriptors Normalized descriptors, keyed by slug.
	 * @param array $config      The `extensions` subtree of the `library` config.
	 * @param array $option      Panel toggles, as slug => bool.
	 * @return array slug => array{enabled:bool,source:string,locked:bool}.
	 */
	public static function resolve_states( array $descriptors, array $config, array $option ): array {
		$states = array();

		foreach ( $descriptors as $slug => $descriptor ) {
			$states[ $slug ] = self::resolve_own_state( $descriptor, $config, $option );
		}

		return $states;
	}

	/**
	 * Resolve a single descriptor's state. Pure.
	 *
	 * @param array $descriptor Normalized descriptor.
	 * @param array $config     The `extensions` subtree of the `library` config.
	 * @param array $option     Panel toggles, as slug => bool.
	 * @return array{enabled:bool,source:string,locked:bool}
	 */
	private static function resolve_own_state( array $descriptor, array $config, array $option ): array {
		if ( ! self::requirement_met( $descriptor['requires'] ) ) {
			return array(
				'enabled' => false,
				'source'  => 'unavailable',
				'locked'  => true,
			);
		}

		$from_code = $config[ $descriptor['slug'] ]['enabled'] ?? null;
		if ( \is_bool( $from_code ) ) {
			return array(
				'enabled' => $from_code,
				'source'  => 'code',
				'locked'  => true,
			);
		}

		$from_panel = $option[ $descriptor['slug'] ] ?? null;
		if ( \is_bool( $from_panel ) ) {
			return array(
				'enabled' => $from_panel,
				'source'  => 'panel',
				'locked'  => false,
			);
		}

		return array(
			'enabled' => $descriptor['default'],
			'source'  => 'default',
			'locked'  => false,
		);
	}

	/*
	 * WordPress adapters. Everything below may call WordPress functions.
	 */

	/**
	 * Cache for descriptors().
	 *
	 * @var array|null
	 */
	private static $descriptors_cache = null;

	/**
	 * Cache for all().
	 *
	 * @var array|null
	 */
	private static $extensions_cache = null;

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function boot(): void {
		/*
		 * Priority 0, ahead of the block Loader's 5 and of everything a theme
		 * adds on init. Post type labels, admin menu entries and the registered
		 * `post` object are all read later in the request, so an extension that
		 * rewrites them has to be in place before anything else on init runs.
		 *
		 * init, not plugins_loaded: state resolution reads isudev.json through
		 * Config, whose `isudev_library/config` filters a theme adds in
		 * functions.php do not exist until after_setup_theme.
		 */
		\add_action( 'init', array( __CLASS__, 'load' ), 0 );
	}

	/**
	 * Discover and normalize every extension descriptor.
	 *
	 * @return array slug => normalized descriptor.
	 */
	public static function descriptors(): array {
		if ( null !== self::$descriptors_cache ) {
			return self::$descriptors_cache;
		}

		$descriptors = array();
		$files       = \glob( PATH . 'extensions/*/extension.php' );
		$files       = \is_array( $files ) ? $files : array();

		\sort( $files );

		foreach ( $files as $file ) {
			$raw = require $file;

			if ( ! \is_array( $raw ) ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Extension descriptor %s must return an array.', $file ) ),
					'1.11.0'
				);
				continue;
			}

			$descriptor = self::normalize_descriptor( $raw );

			if ( null === $descriptor ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Extension descriptor %s must define non-empty "slug" and "title".', $file ) ),
					'1.11.0'
				);
				continue;
			}

			$expected_slug = \basename( \dirname( $file ) );
			if ( $descriptor['slug'] !== $expected_slug ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Extension descriptor %1$s declares slug "%2$s" but lives in directory "%3$s".', $file, $descriptor['slug'], $expected_slug ) ),
					'1.11.0'
				);
				continue;
			}

			$descriptors[ $descriptor['slug'] ] = $descriptor;
		}

		self::$descriptors_cache = $descriptors;

		return $descriptors;
	}

	/**
	 * The `extensions` subtree of the merged isudev.json `library` config.
	 *
	 * @return array slug => array{enabled?:bool}.
	 */
	public static function config(): array {
		$config = Config\get_config()[ self::CONFIG_KEY ] ?? array();

		return \is_array( $config ) ? $config : array();
	}

	/**
	 * One setting of an extension from isudev.json: `library.extensions.<slug>.<key>`.
	 *
	 * Lives next to `enabled` under the same slug, so a theme configures an
	 * extension in the one place it can also pin it on or off.
	 *
	 * @param string $slug     Extension slug.
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Returned when the key is absent.
	 * @return mixed
	 */
	public static function setting( string $slug, string $key, $fallback = null ) {
		$config = self::config()[ $slug ] ?? array();

		if ( \is_array( $config ) && \array_key_exists( $key, $config ) ) {
			return $config[ $key ];
		}

		$panel = self::panel_settings( $slug );

		return \array_key_exists( $key, $panel ) ? $panel[ $key ] : $fallback;
	}

	/**
	 * Settings an operator saved in the panel for one extension, limited to the
	 * fields its descriptor declares.
	 *
	 * @param string $slug Extension slug.
	 * @return array key => value.
	 */
	public static function panel_settings( string $slug ): array {
		$option = \get_option( self::SETTINGS_OPTION, array() );
		$saved  = \is_array( $option ) && \is_array( $option[ $slug ] ?? null ) ? $option[ $slug ] : array();
		$fields = self::descriptors()[ $slug ]['settings'] ?? array();
		$values = array();

		foreach ( $fields as $field ) {
			if ( \array_key_exists( $field['key'], $saved ) ) {
				$values[ $field['key'] ] = self::sanitize_setting_value( $field, $saved[ $field['key'] ] );
			}
		}

		return $values;
	}

	/**
	 * Enqueue an extension script compiled by `npm run build`.
	 *
	 * Source is `extensions/<slug>/src/<entry>.js`, output
	 * `build/extensions/<slug>/<entry>.js` with its `.asset.php`. A stylesheet
	 * the entry imports is enqueued alongside. Static scripts that need no
	 * build (see button-color) are enqueued by the extension itself.
	 *
	 * @param string $slug  Extension slug.
	 * @param string $entry Entry name. Default `editor`.
	 * @return string Script handle, or '' when the build output is missing.
	 */
	public static function enqueue_build( string $slug, string $entry = 'editor' ): string {
		$relative = 'build/extensions/' . $slug . '/' . $entry;
		$asset    = PATH . $relative . '.asset.php';

		if ( ! \is_readable( $asset ) ) {
			return '';
		}

		$meta   = require $asset;
		$meta   = \is_array( $meta ) ? $meta : array();
		$handle = 'isudev-library-' . $slug . '-' . $entry;

		\wp_enqueue_script(
			$handle,
			URL . $relative . '.js',
			(array) ( $meta['dependencies'] ?? array() ),
			(string) ( $meta['version'] ?? VERSION ),
			true
		);

		\wp_set_script_translations( $handle, 'isudev-library', PATH . 'languages' );

		if ( \is_readable( PATH . $relative . '.css' ) ) {
			\wp_enqueue_style(
				$handle,
				URL . $relative . '.css',
				array(),
				(string) ( $meta['version'] ?? VERSION )
			);
		}

		return $handle;
	}

	/**
	 * Descriptors decorated with resolved state.
	 *
	 * @return array slug => descriptor + enabled/source/locked.
	 */
	public static function all(): array {
		if ( null !== self::$extensions_cache ) {
			return self::$extensions_cache;
		}

		$descriptors = self::descriptors();
		$option      = \get_option( self::OPTION, array() );
		$option      = \is_array( $option ) ? $option : array();

		$states = self::resolve_states( $descriptors, self::config(), $option );

		$extensions = array();
		foreach ( $descriptors as $slug => $descriptor ) {
			$extensions[ $slug ] = \array_merge( $descriptor, $states[ $slug ] );
		}

		self::$extensions_cache = $extensions;

		return $extensions;
	}

	/**
	 * Whether an extension is enabled.
	 *
	 * @param string $slug Extension slug.
	 * @return bool
	 */
	public static function is_enabled( string $slug ): bool {
		$extensions = self::all();

		return isset( $extensions[ $slug ] ) && $extensions[ $slug ]['enabled'];
	}

	/**
	 * Require and boot every enabled extension.
	 *
	 * @return void
	 */
	public static function load(): void {
		foreach ( self::all() as $slug => $extension ) {
			if ( ! $extension['enabled'] ) {
				continue;
			}

			$root = PATH . 'extensions/' . $slug . '/';

			foreach ( $extension['bootstrap'] as $relative ) {
				$file = Loader::contained_path( $root, $relative );

				if ( '' === $file ) {
					\_doing_it_wrong(
						__METHOD__,
						\esc_html( \sprintf( 'Extension "%1$s" declares a bootstrap file that is missing or outside its own directory: %2$s', $slug, $relative ) ),
						'1.11.0'
					);
					continue;
				}

				require_once $file;
			}

			if ( '' === $extension['boot'] ) {
				continue;
			}

			if ( ! \is_callable( $extension['boot'] ) ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Extension "%1$s" declares boot callback "%2$s", which is not callable. Check its bootstrap files.', $slug, $extension['boot'] ) ),
					'1.11.0'
				);
				continue;
			}

			\call_user_func( $extension['boot'] );
		}
	}

	/**
	 * Clear the per-request caches.
	 *
	 * @return void
	 */
	public static function flush(): void {
		self::$descriptors_cache = null;
		self::$extensions_cache  = null;
	}
}
