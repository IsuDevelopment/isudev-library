<?php
/**
 * Descriptor for the isudev/image-step-guide block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'image-step-guide',
	'name'       => 'isudev/image-step-guide',
	'requires'   => array(),
	'always_on'  => false,
	'variations' => true,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
