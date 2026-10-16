<?php
/**
 * Descriptor for the Reset admin dashboard extension.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

return array(
	'slug'        => 'reset-admin-dashboard',
	'title'       => \__( 'Clean up the dashboard', 'isudev-library' ),
	'description' => \__( 'Removes the default widgets from the admin dashboard — Welcome, At a Glance, Activity, Quick Draft, WordPress Events and News, Site Health and common plugin widgets. Security warnings about PHP and the browser stay.', 'isudev-library' ),
	'category'    => 'admin',
	'default'     => false,
	'bootstrap'   => array( 'hooks.php' ),
	'boot'        => 'IsuDevLibrary\\Extensions\\ResetAdminDashboard\\boot',
);
