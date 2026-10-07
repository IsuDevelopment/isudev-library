<?php
/**
 * Descriptor for the Disable comments extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'disable-comments',
	'title'       => \__( 'Disable comments', 'isudev-library' ),
	'description' => \__( 'Turns comments and pingbacks off site-wide: closes them on every post type, hides existing ones and removes Comments from the admin.', 'isudev-library' ),
	'category'    => 'content',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisableComments\\boot',
);
