<?php
/**
 * Descriptor for the Disable posts extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'disable-posts',
	'title'       => \__( 'Disable posts', 'isudev-library' ),
	'description' => \__( 'Hides the built-in Posts post type, its categories and tags: no admin menu, no front-end archives, no single views.', 'isudev-library' ),
	'category'    => 'content',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisablePosts\\boot',
);
