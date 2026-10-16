<?php
/**
 * Descriptor for the Disable application passwords extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'disable-application-passwords',
	'title'       => \__( 'Disable application passwords', 'isudev-library' ),
	'description' => \__( 'Switches application passwords off: the profile section disappears and existing application passwords stop authenticating REST API and XML-RPC requests. Breaks apps and integrations that sign in with one.', 'isudev-library' ),
	'category'    => 'security',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\DisableApplicationPasswords\\boot',
);
