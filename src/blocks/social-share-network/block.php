<?php
/**
 * Descriptor for the isudev/social-share-network block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'social-share-network',
	'name'       => 'isudev/social-share-network',
	// Cascades: disabling the parent in the admin panel disables this block too.
	'requires'   => array( 'social-share' ),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
