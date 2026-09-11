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
			'accessibility' => \__( 'Accessibility', 'isudev-library' ),
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
		);
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
