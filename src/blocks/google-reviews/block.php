<?php
/**
 * Descriptor for the isudev/google-reviews block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'google-reviews',
	'name'       => 'isudev/google-reviews',
	'requires'   => array(),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
