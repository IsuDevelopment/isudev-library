<?php
/**
 * Option registry handed to `isudev_library/post_card/register_options`.
 *
 * @package IsuDevLibrary\PostCards
 */

declare( strict_types = 1 );

namespace IsuDevLibrary\PostCards;

defined( 'ABSPATH' ) || exit;

/**
 * Collects option definitions. Handed to `isudev_library/post_card/register_options`.
 */
final class Option_Registry {

	/**
	 * Normalised definitions keyed by option key.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Add or replace an option.
	 *
	 * @param string $key Option key, `[A-Za-z][A-Za-z0-9]*`.
	 * @param array  $def Definition; see guides/post-cards.md.
	 * @return bool False when the definition is invalid.
	 */
	public function add_option( string $key, array $def ): bool {
		$normalized = normalize_option( $key, $def );

		if ( null === $normalized ) {
			return false;
		}

		$this->options[ $key ] = $normalized;

		return true;
	}

	/**
	 * Remove an option, including a built-in one.
	 *
	 * @param string $key Option key.
	 * @return void
	 */
	public function remove_option( string $key ): void {
		unset( $this->options[ $key ] );
	}

	/**
	 * Every definition, keyed by option key.
	 *
	 * @return array
	 */
	public function all(): array {
		return $this->options;
	}
}
