<?php
/**
 * Descriptor for the Featured image column extension.
 *
 * Returns metadata only — it registers nothing and defines no hooks. Extensions
 * boots hooks.php and calls its boot() only when this extension is enabled.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'featured-image-column',
	'title'       => \__( 'Featured image column', 'isudev-library' ),
	'description' => \__( 'Shows a thumbnail of the featured image at the start of every list screen whose post type supports featured images, and the profile image on the Users screen when one is set.', 'isudev-library' ),
	'category'    => 'admin',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\FeaturedImageColumn\\boot',
	'settings'    => array(
		array(
			'key'      => 'hiddenPostTypes',
			'type'     => 'post_types',
			'supports' => 'thumbnail',
			'label'    => \__( 'Hide the column on', 'isudev-library' ),
		),
		array(
			'key'   => 'userMetaKey',
			'type'  => 'text',
			'label' => \__( 'User image meta key', 'isudev-library' ),
			'help'  => \__( 'The user field that holds a profile image (attachment ID or URL). Empty: no column on Users.', 'isudev-library' ),
		),
	),
);
