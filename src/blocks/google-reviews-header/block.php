<?php
/**
 * Descriptor for the isudev/google-reviews-header block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'google-reviews-header',
	'name'       => 'isudev/google-reviews-header',
	'requires'   => array(),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
