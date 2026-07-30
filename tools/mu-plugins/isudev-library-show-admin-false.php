<?php
/**
 * Plugin Name: IsuDev Library — force show_admin false
 * Description: Dev-only. Drop into wp-content/mu-plugins/ to verify that hiding
 *              the panel also closes the REST endpoints. Delete afterwards.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

add_filter( 'isudev_library/settings/show_admin', '__return_false' );
