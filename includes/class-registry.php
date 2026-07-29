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
}
