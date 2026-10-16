<?php
/**
 * Descriptor for the Accordion FAQ structured data extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'core-accordion-faq-schema',
	'title'       => \__( 'Accordion FAQ structured data', 'isudev-library' ),
	'description' => \__( 'Adds a switch to the core Accordion block that publishes its questions and answers as FAQPage structured data (JSON-LD), through Yoast SEO or Rank Math when one is active, or on its own otherwise.', 'isudev-library' ),
	'category'    => 'blocks',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\CoreAccordionFaqSchema\\boot',
);
