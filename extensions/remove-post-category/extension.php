<?php
/**
 * Descriptor for the Remove post categories extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'remove-post-category',
	'title'       => \__( 'Remove categories from posts', 'isudev-library' ),
	'description' => \__( 'Keeps posts but drops categories: no Categories screen or editor panel, and when nothing else uses categories, their archives answer 404 and they leave REST, sitemaps and the inserter.', 'isudev-library' ),
	'category'    => 'content',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\RemovePostCategory\\boot',
);
