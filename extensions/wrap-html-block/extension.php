<?php
/**
 * Descriptor for the Custom HTML block wrapper extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'wrap-html-block',
	'title'       => \__( 'Custom HTML block wrapper', 'isudev-library' ),
	'description' => \__( 'Wraps the rendered Custom HTML block in a container, so its markup and any trailing script stay together in the layout.', 'isudev-library' ),
	'category'    => 'content',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\WrapHtmlBlock\\boot',
);
