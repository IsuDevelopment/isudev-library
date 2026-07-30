<?php
/**
 * Descriptor for the isudev/site-header block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'site-header',
	'name'       => 'isudev/site-header',
	'requires'   => array(),
	'always_on'  => false,
	'variations' => true,
	'bootstrap'  => array(
		'inc/render-helpers.php',
		'inc/class-nav-walker.php',
	),
);
