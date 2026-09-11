<?php
/**
 * Descriptor for the Gravity Forms theme lock extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'gravity-forms-theme-lock',
	'title'       => \__( 'Lock the Gravity Forms block theme', 'isudev-library' ),
	'description' => \__( 'Pins every Gravity Forms block to one form theme and hides the theme picker, so forms cannot drift from the site styles.', 'isudev-library' ),
	'category'    => 'plugins',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\GravityFormsThemeLock\\boot',
	'requires'    => array(
		'label' => 'Gravity Forms',
		'class' => 'GFForms',
	),
);
