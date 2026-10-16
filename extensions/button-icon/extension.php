<?php
/**
 * Descriptor for the Button icon extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'button-icon',
	'title'       => \__( 'Button icon', 'isudev-library' ),
	'description' => \__( 'Lets editors put an icon from the library\'s icon set before or after the text of a Button block, choose its size, or show the icon alone with the text kept for screen readers.', 'isudev-library' ),
	'category'    => 'blocks',
	'default'     => false,
	'bootstrap'   => array( 'inc/attributes.php', 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\ButtonIcon\\boot',
);
