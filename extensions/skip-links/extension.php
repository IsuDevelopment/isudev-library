<?php
/**
 * Descriptor for the Skip links extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'skip-links',
	'title'       => \__( 'Skip links', 'isudev-library' ),
	'description' => \__( 'Adds a keyboard-only skip navigation right after the opening body tag, replacing the core block theme skip link.', 'isudev-library' ),
	'category'    => 'accessibility',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\SkipLinks\\boot',
);
