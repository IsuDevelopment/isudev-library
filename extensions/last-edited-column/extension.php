<?php
/**
 * Descriptor for the Last edited column extension.
 *
 * Returns metadata only — it registers nothing and defines no hooks. Extensions
 * boots hooks.php and calls its boot() only when this extension is enabled.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'last-edited-column',
	'title'       => \__( 'Last edited column', 'isudev-library' ),
	'description' => \__( 'Adds a sortable "Last edited" column to post type list screens, showing who changed a post and when.', 'isudev-library' ),
	'category'    => 'admin',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\LastEditedColumn\\boot',
);
