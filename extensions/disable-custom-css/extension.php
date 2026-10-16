<?php
/**
 * Descriptor for the Disable custom CSS extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'disable-custom-css',
	'title'       => \__( 'Disable custom CSS', 'isudev-library' ),
	'description' => \__( 'Takes CSS out of editors\' hands: removes Additional CSS from the Customizer, Global Styles and the block settings, and stops CSS already saved there from loading. CSS shipped by the theme keeps working.', 'isudev-library' ),
	'category'    => 'editor',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisableCustomCss\\boot',
);
