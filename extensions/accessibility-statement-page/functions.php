<?php
/**
 * Global template tags for themes. Defined only while the extension is enabled, so call
 * them behind function_exists().
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

use IsuDevLibrary\Extensions\AccessibilityStatementPage as Statement;

if ( ! function_exists( 'isudev_library_get_accessibility_statement_url' ) ) {
	/**
	 * URL of the published accessibility statement page; empty when none is published.
	 *
	 * @return string
	 */
	function isudev_library_get_accessibility_statement_url(): string {
		return Statement\url();
	}
}

if ( ! function_exists( 'isudev_library_get_accessibility_statement_link' ) ) {
	/**
	 * Link to the published accessibility statement page, wrapped in $before/$after; empty when none is published.
	 *
	 * @param string $before Markup before the link.
	 * @param string $after  Markup after the link.
	 * @return string
	 */
	function isudev_library_get_accessibility_statement_link( string $before = '', string $after = '' ): string {
		return Statement\statement_link( $before, $after );
	}
}

if ( ! function_exists( 'isudev_library_is_accessibility_statement' ) ) {
	/**
	 * Whether the main query is for the accessibility statement page.
	 *
	 * @return bool
	 */
	function isudev_library_is_accessibility_statement(): bool {
		return Statement\is_statement_page();
	}
}
