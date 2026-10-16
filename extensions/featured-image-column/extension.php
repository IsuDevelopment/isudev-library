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
);
