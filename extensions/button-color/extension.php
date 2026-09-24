<?php
/**
 * Descriptor for the Button colour property extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'button-color',
	'title'       => \__( 'Button colour for outline and link styles', 'isudev-library' ),
	'description' => \__( 'Exposes the colour picked on a Button block as a CSS custom property, in the editor and on the site, so a theme can draw outline and text-only buttons (and their icons) in that colour. Also removes core\'s Outline style, which turns into a filled button as soon as a colour is picked.', 'isudev-library' ),
	'category'    => 'content',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\ButtonColor\\boot',
);
