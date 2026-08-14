<?php
/**
 * Descriptor for the isudev/image-step-guide-step block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'image-step-guide-step',
	'name'       => 'isudev/image-step-guide-step',
	// Cascades: disabling the parent in the admin panel disables this block too.
	'requires'   => array( 'image-step-guide' ),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
