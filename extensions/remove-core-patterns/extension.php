<?php
/**
 * Descriptor for the Remove core patterns extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'remove-core-patterns',
	'title'       => \__( 'Remove core patterns', 'isudev-library' ),
	'description' => \__( 'Removes the patterns WordPress itself ships and the ones it downloads from the WordPress.org pattern directory, so the pattern inserter lists only the theme\'s and the site\'s own patterns.', 'isudev-library' ),
	'category'    => 'editor',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\RemoveCorePatterns\\boot',
);
