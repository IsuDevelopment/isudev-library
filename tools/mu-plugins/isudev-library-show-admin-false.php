<?php
/**
 * Plugin Name: IsuDev Library — force show_admin false
 * Description: Dev-only. Drop into wp-content/mu-plugins/ to verify that hiding
 *              the panel also closes the REST endpoints. Delete afterwards.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/*
 * Same guard as the dev fixture beside this file: exact match on the Local
 * dev host, port stripped, empty host allowed (CLI/cron on this install).
 * Without it, this file would gate the panel and REST endpoints on any
 * install it was ever mistakenly copied into.
 */
$isudev_dev_host = 'isudev-library.local';
$isudev_req_host = strtolower( (string) strtok( (string) ( $_SERVER['HTTP_HOST'] ?? '' ), ':' ) );

if ( '' !== $isudev_req_host && $isudev_dev_host !== $isudev_req_host ) {
	return;
}

add_filter( 'isudev_library/settings/show_admin', '__return_false' );
