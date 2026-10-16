<?php
/**
 * Descriptor for the Accordion links extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'core-accordion-hash-navigation',
	'title'       => \__( 'Accordion links', 'isudev-library' ),
	'description' => \__( 'Gives every core Accordion item a stable, readable link (#question-title, or the heading\'s HTML anchor). Opening an item puts its link in the address bar, and visiting a link opens and scrolls to that item.', 'isudev-library' ),
	'category'    => 'blocks',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\CoreAccordionHashNavigation\\boot',
);
