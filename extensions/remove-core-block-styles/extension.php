<?php
/**
 * Descriptor for the Remove core block styles extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'remove-core-block-styles',
	'title'       => \__( 'Remove core block styles', 'isudev-library' ),
	'description' => \__( 'Removes the styles WordPress ships for its own blocks (Outline button, Rounded image, Stripes table and the like) from the Styles panel, so editors choose only from the styles the theme registers. The theme can keep chosen ones.', 'isudev-library' ),
	'category'    => 'editor',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\RemoveCoreBlockStyles\\boot',
);
