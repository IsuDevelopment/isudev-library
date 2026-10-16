<?php
/**
 * Descriptor for the Hide author archives and user lists extension.
 *
 * The slug stays `redirect-authors` for parity with the T2 extension it replaces.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'redirect-authors',
	'title'       => \__( 'Hide author archives and user lists', 'isudev-library' ),
	'description' => \__( 'Author archives answer 404 instead of revealing login names, and visitors can no longer list users through the REST API, oEmbed or the users sitemap. Signed-in editors keep the editor\'s author picker.', 'isudev-library' ),
	'category'    => 'security',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\RedirectAuthors\\boot',
);
