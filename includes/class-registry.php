<?php
/**
 * Block registry: descriptor discovery and enabled-state resolution.
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
 * Discovers block descriptors and resolves which blocks are enabled.
 */
class Registry {

	/**
	 * Option holding panel-managed toggles, as slug => bool.
	 *
	 * @var string
	 */
	const OPTION = 'isudev_library_blocks';

	/**
	 * Normalize a raw descriptor, filling every key. Pure.
	 *
	 * @param array $raw Descriptor as returned by a block's block.php.
	 * @return array|null Normalized descriptor, or null when invalid.
	 */
	public static function normalize_descriptor( array $raw ): ?array {
		$slug = $raw['slug'] ?? null;
		$name = $raw['name'] ?? null;

		if ( ! \is_string( $slug ) || '' === $slug || ! \is_string( $name ) || '' === $name ) {
			return null;
		}

		return array(
			'slug'       => $slug,
			'name'       => $name,
			'requires'   => self::string_list( $raw['requires'] ?? array() ),
			'always_on'  => (bool) ( $raw['always_on'] ?? false ),
			'variations' => (bool) ( $raw['variations'] ?? false ),
			'bootstrap'  => self::string_list( $raw['bootstrap'] ?? array() ),
		);
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
	 * Invert `requires` into a slug => dependents map. Pure.
	 *
	 * The returned keys are exactly the slugs present in $descriptors — every one
	 * of them, and no others. A `requires` entry naming an unknown slug is
	 * ignored here rather than inventing a key for a block that does not exist.
	 *
	 * @param array $descriptors Normalized descriptors, keyed by slug.
	 * @return array slug => list of slugs that require it.
	 */
	public static function build_dependents( array $descriptors ): array {
		$dependents = array();

		foreach ( \array_keys( $descriptors ) as $slug ) {
			$dependents[ $slug ] = array();
		}

		foreach ( $descriptors as $slug => $descriptor ) {
			foreach ( $descriptor['requires'] as $required ) {
				/*
				 * A requires entry naming an unknown slug creates no key, so the
				 * map's keys are exactly the known slugs and nothing downstream
				 * can read a phantom entry. resolve_states() already treats the
				 * unknown requirement as unmet, so the block ends as `dependency`.
				 */
				if ( ! isset( $dependents[ $required ] ) ) {
					continue;
				}

				$dependents[ $required ][] = $slug;
			}
		}

		return $dependents;
	}

	/**
	 * Resolve enabled state for every descriptor. Pure.
	 *
	 * Precedence (spec §7): unmet requires, then always_on, then isudev.json,
	 * then the panel option, then enabled by default.
	 *
	 * @param array $descriptors Normalized descriptors, keyed by slug.
	 * @param array $config      The `library` subtree from isudev.json.
	 * @param array $option      Panel toggles, as slug => bool.
	 * @return array slug => array{enabled:bool,source:string,locked:bool}.
	 */
	public static function resolve_states( array $descriptors, array $config, array $option ): array {
		$states = array();

		// Pass 1: own state, ignoring dependencies.
		foreach ( $descriptors as $slug => $descriptor ) {
			$states[ $slug ] = self::resolve_own_state( $descriptor, $config, $option );
		}

		// Pass 2: cascade unmet dependencies until the result is stable.
		$changed = true;
		while ( $changed ) {
			$changed = false;

			foreach ( $descriptors as $slug => $descriptor ) {
				if ( 'dependency' === $states[ $slug ]['source'] ) {
					continue;
				}

				foreach ( $descriptor['requires'] as $required ) {
					$satisfied = isset( $states[ $required ] ) && $states[ $required ]['enabled'];

					if ( ! $satisfied ) {
						$states[ $slug ] = array(
							'enabled' => false,
							'source'  => 'dependency',
							'locked'  => true,
						);
						$changed         = true;
						break;
					}
				}
			}
		}

		return $states;
	}

	/**
	 * Resolve a single descriptor's state, ignoring dependencies. Pure.
	 *
	 * @param array $descriptor Normalized descriptor.
	 * @param array $config     The `library` subtree from isudev.json.
	 * @param array $option     Panel toggles, as slug => bool.
	 * @return array{enabled:bool,source:string,locked:bool}
	 */
	private static function resolve_own_state( array $descriptor, array $config, array $option ): array {
		if ( $descriptor['always_on'] ) {
			return array(
				'enabled' => true,
				'source'  => 'always_on',
				'locked'  => true,
			);
		}

		$from_code = $config[ $descriptor['name'] ]['enabled'] ?? null;
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
			'enabled' => true,
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
	 * Cache for blocks().
	 *
	 * @var array|null
	 */
	private static $blocks_cache = null;

	/**
	 * Discover and normalize every block descriptor.
	 *
	 * Descriptors are read from src/, not build/: PHP needs no compilation, so
	 * editing it takes effect without a rebuild.
	 *
	 * @return array slug => normalized descriptor.
	 */
	public static function descriptors(): array {
		if ( null !== self::$descriptors_cache ) {
			return self::$descriptors_cache;
		}

		$descriptors = array();
		$files       = \glob( PATH . 'src/blocks/*/block.php' );
		$files       = \is_array( $files ) ? $files : array();

		\sort( $files );

		foreach ( $files as $file ) {
			$raw = require $file;

			if ( ! \is_array( $raw ) ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Block descriptor %s must return an array.', $file ) ),
					'1.0.0'
				);
				continue;
			}

			$descriptor = self::normalize_descriptor( $raw );

			if ( null === $descriptor ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Block descriptor %s must define non-empty "slug" and "name".', $file ) ),
					'1.0.0'
				);
				continue;
			}

			$expected_slug = \basename( \dirname( $file ) );
			if ( $descriptor['slug'] !== $expected_slug ) {
				\_doing_it_wrong(
					__METHOD__,
					\esc_html( \sprintf( 'Block descriptor %1$s declares slug "%2$s" but lives in directory "%3$s".', $file, $descriptor['slug'], $expected_slug ) ),
					'1.0.0'
				);
				continue;
			}

			$descriptors[ $descriptor['slug'] ] = $descriptor;
		}

		self::$descriptors_cache = $descriptors;

		return $descriptors;
	}

	/**
	 * Descriptors decorated with resolved state and dependents.
	 *
	 * @return array slug => descriptor + enabled/source/locked/dependents.
	 */
	public static function blocks(): array {
		if ( null !== self::$blocks_cache ) {
			return self::$blocks_cache;
		}

		$descriptors = self::descriptors();
		$option      = \get_option( self::OPTION, array() );
		$option      = \is_array( $option ) ? $option : array();

		$states     = self::resolve_states( $descriptors, Config\get_config(), $option );
		$dependents = self::build_dependents( $descriptors );

		$blocks = array();
		foreach ( $descriptors as $slug => $descriptor ) {
			$blocks[ $slug ] = \array_merge(
				$descriptor,
				$states[ $slug ],
				array( 'dependents' => $dependents[ $slug ] ?? array() )
			);
		}

		self::$blocks_cache = $blocks;

		return $blocks;
	}

	/**
	 * Whether a block is enabled.
	 *
	 * @param string $slug Block slug.
	 * @return bool
	 */
	public static function is_enabled( string $slug ): bool {
		$blocks = self::blocks();

		return isset( $blocks[ $slug ] ) && $blocks[ $slug ]['enabled'];
	}

	/**
	 * Clear the per-request caches.
	 *
	 * @return void
	 */
	public static function flush(): void {
		self::$descriptors_cache = null;
		self::$blocks_cache      = null;
	}
}
