<?php
/**
 * Removes this plugin's options when it is deleted from the admin.
 *
 * Deactivation leaves everything alone on purpose — only an explicit delete
 * reaches this file.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'isudev_library_blocks' );
delete_option( 'isudev_library_settings' );
