<?php
/**
 * Descriptor for the Rename posts extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'post-rename',
	'title'       => \__( 'Rename posts to articles', 'isudev-library' ),
	'description' => \__( 'Relabels the built-in Posts post type as Articles across the admin. Labels are filterable, so any other word works too.', 'isudev-library' ),
	'category'    => 'content',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\PostRename\\boot',
);
