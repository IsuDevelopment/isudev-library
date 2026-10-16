<?php
/**
 * Descriptor for the Remove post tags extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'remove-post-tags',
	'title'       => \__( 'Remove tags from posts', 'isudev-library' ),
	'description' => \__( 'Keeps posts but drops tags: no Tags screen or editor panel, and when nothing else uses tags, their archives answer 404 and they leave REST, sitemaps and the inserter.', 'isudev-library' ),
	'category'    => 'content',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\RemovePostTags\\boot',
);
