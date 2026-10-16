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
	'description' => \__( 'Switches off the built-in Posts post type with its categories and tags: no admin menu, every post URL, archive and posts feed answers 404, and posts leave REST, XML-RPC, sitemaps and the block inserter. Set a static homepage first.', 'isudev-library' ),
	'category'    => 'content',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisablePosts\\boot',
);
