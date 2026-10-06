<?php
/**
 * Descriptor for the isudev/content-sidebar-aside block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'content-sidebar-aside',
	'name'       => 'isudev/content-sidebar-aside',
	// Cascades: disabling the parent in the admin panel disables this block too.
	'requires'   => array( 'content-sidebar' ),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(),
);
