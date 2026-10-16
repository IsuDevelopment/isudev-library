<?php
/**
 * Descriptor for the Disable block directory extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'disable-block-directory',
	'title'       => \__( 'Disable the block directory', 'isudev-library' ),
	'description' => \__( 'Stops the block inserter from suggesting and installing blocks from WordPress.org, and closes the block directory search endpoint. Plugins can still be installed from Plugins → Add New.', 'isudev-library' ),
	'category'    => 'editor',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisableBlockDirectory\\boot',
);
