<?php
/**
 * Descriptor for the isudev/selling-point block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'selling-point',
	'name'       => 'isudev/selling-point',
	// Cascades: disabling the parent in the admin panel disables this block too.
	'requires'   => array( 'selling-points' ),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
