<?php
/**
 * Descriptor for the Disable emoji extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'disable-emoji',
	'title'       => \__( 'Disable emoji images', 'isudev-library' ),
	'description' => \__( 'Stops WordPress from replacing emoji with images from WordPress.org: removes the detection script and its styles from every page, the admin and embeds. Emoji still show, drawn by the visitor\'s own system font.', 'isudev-library' ),
	'category'    => 'theme',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisableEmoji\\boot',
);
