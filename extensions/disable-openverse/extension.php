<?php
/**
 * Descriptor for the Disable Openverse extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'disable-openverse',
	'title'       => \__( 'Disable Openverse', 'isudev-library' ),
	'description' => \__( 'Removes the Openverse tab from the block inserter\'s Media section, so editors pick images from the Media Library only instead of hotlinking free-licence images from openverse.org.', 'isudev-library' ),
	'category'    => 'editor',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisableOpenverse\\boot',
);
