<?php
/**
 * Descriptor for the isudev/toggle-blocks block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'toggle-blocks',
	'name'       => 'isudev/toggle-blocks',
	'requires'   => array(),
	'always_on'  => false,
	'variations' => true,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
