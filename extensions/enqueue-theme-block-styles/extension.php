<?php
/**
 * Descriptor for the Enqueue theme block styles extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'enqueue-theme-block-styles',
	'title'       => \__( 'Load theme block styles per block', 'isudev-library' ),
	'description' => \__( 'Loads each stylesheet the theme builds for a block (build/blocks/<namespace>/<block>.css) only on pages where that block appears, and in the editor. For themes built on the Dekode project base.', 'isudev-library' ),
	'category'    => 'theme',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\EnqueueThemeBlockStyles\\boot',
);
