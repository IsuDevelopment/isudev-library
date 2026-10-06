<?php
/**
 * Descriptor for the isudev/content-sidebar block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'content-sidebar',
	'name'       => 'isudev/content-sidebar',
	'requires'   => array(),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
