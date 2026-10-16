<?php
/**
 * Descriptor for the Accessibility statement page extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'accessibility-statement-page',
	'title'       => \__( 'Accessibility statement page', 'isudev-library' ),
	'description' => \__( 'Adds Settings → Accessibility statement, like core\'s Privacy page: pick the page with your accessibility statement or create a draft from a template (the Polish public-sector declaration or the EU model statement). Marks the page in the Pages list, protects it from deletion and warns in Site Health when it is missing, unpublished or older than a year.', 'isudev-library' ),
	'category'    => 'accessibility',
	'default'     => false,
	'bootstrap'   => array( 'template.php', 'hooks.php', 'functions.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\AccessibilityStatementPage\\boot',
);
