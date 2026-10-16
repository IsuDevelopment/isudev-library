<?php
/**
 * Descriptor for the Disable feeds extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'disable-feeds',
	'title'       => \__( 'Disable feeds', 'isudev-library' ),
	'description' => \__( 'Switches off every RSS, RDF and Atom feed, including comment feeds: feed URLs answer with the theme\'s 404 page and the feed links leave the page head.', 'isudev-library' ),
	'category'    => 'content',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisableFeeds\\boot',
);
