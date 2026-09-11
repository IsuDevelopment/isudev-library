<?php
/**
 * Descriptor for the Gravity Forms wrapper extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'gravity-forms-wrapper',
	'title'       => \__( 'Gravity Forms block wrapper', 'isudev-library' ),
	'description' => \__( 'Wraps the rendered Gravity Forms block in a styleable container, because the block outputs the form without one.', 'isudev-library' ),
	'category'    => 'plugins',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\GravityFormsWrapper\\boot',
	'requires'    => array(
		'label' => 'Gravity Forms',
		'class' => 'GFForms',
	),
);
