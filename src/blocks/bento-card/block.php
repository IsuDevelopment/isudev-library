<?php
/**
 * Descriptor for the isudev/bento-card block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'bento-card',
	'name'       => 'isudev/bento-card',
	// Cascades: disabling the parent in the admin panel disables this block too.
	'requires'   => array( 'bento-grid' ),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
