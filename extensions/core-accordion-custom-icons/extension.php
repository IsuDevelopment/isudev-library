<?php
/**
 * Descriptor for the Accordion toggle icons extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'core-accordion-custom-icons',
	'title'       => \__( 'Accordion toggle icons', 'isudev-library' ),
	'description' => \__( 'Replaces the "+" on the core Accordion block with an icon from the icon library, one for closed and one for open items. Editors pick the icons and their size per accordion; the theme sets the defaults.', 'isudev-library' ),
	'category'    => 'blocks',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\CoreAccordionCustomIcons\\boot',
);
