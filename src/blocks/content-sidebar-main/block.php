<?php
/**
 * Descriptor for the isudev/content-sidebar-main block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'content-sidebar-main',
	'name'       => 'isudev/content-sidebar-main',
	// Cascades: disabling the parent in the admin panel disables this block too.
	'requires'   => array( 'content-sidebar' ),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(),
);
