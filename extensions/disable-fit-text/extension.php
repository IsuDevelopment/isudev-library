<?php
/**
 * Descriptor for the Disable fit text extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'disable-fit-text',
	'title'       => \__( 'Disable fit text', 'isudev-library' ),
	'description' => \__( 'Removes the "Fit text" typography option, which scales a heading or paragraph to fill its container with JavaScript and ignores the theme\'s font sizes. Blocks saved with it render at their normal size again.', 'isudev-library' ),
	'category'    => 'editor',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisableFitText\\boot',
);
