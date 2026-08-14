<?php
/**
 * Descriptor for the isudev/agenda-accordion-item block.
 *
 * Returns metadata only. Registration happens in IsuDevLibrary\Loader.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'       => 'agenda-accordion-item',
	'name'       => 'isudev/agenda-accordion-item',
	// Cascades: disabling the parent in the admin panel disables this block too.
	'requires'   => array( 'agenda-accordion' ),
	'always_on'  => false,
	'variations' => false,
	'bootstrap'  => array(
		'inc/render-helpers.php',
	),
);
