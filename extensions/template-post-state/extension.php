<?php
/**
 * Descriptor for the Template name in list screens extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'template-post-state',
	'title'       => \__( 'Show template name', 'isudev-library' ),
	'description' => \__( 'Shows the assigned page template as a badge next to the title on post type list screens.', 'isudev-library' ),
	'category'    => 'admin',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\TemplatePostState\\boot',
);
