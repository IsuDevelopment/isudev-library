<?php
/**
 * Descriptor for the Site logo in General settings extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'site-logo-option',
	'title'       => \__( 'Site logo in General settings', 'isudev-library' ),
	'description' => \__( 'Adds a site logo picker to Settings → General, so the logo can be changed without opening the site editor.', 'isudev-library' ),
	'category'    => 'admin',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\SiteLogoOption\\boot',
);
