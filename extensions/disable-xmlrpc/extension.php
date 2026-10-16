<?php
/**
 * Descriptor for the Disable XML-RPC extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'disable-xmlrpc',
	'title'       => \__( 'Disable XML-RPC', 'isudev-library' ),
	'description' => \__( 'Refuses every request to xmlrpc.php with a 403 before any method runs, closing a common brute-force and pingback abuse route. Breaks Jetpack and old desktop or mobile publishing apps that still use XML-RPC.', 'isudev-library' ),
	'category'    => 'security',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisableXmlrpc\\boot',
);
