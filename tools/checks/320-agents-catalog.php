<?php
/**
 * Checks that agents/README.md — the guide agents in consuming projects read —
 * matches the code: every block, extension and hook is in the catalog, and
 * every hook is explained in a shipped doc. Fix with `php tools/agents-catalog.php`.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

require_once dirname( __DIR__ ) . '/agents-catalog.php';

$isudev_root    = dirname( __DIR__, 2 ) . '/';
$isudev_guide   = (string) file_get_contents( $isudev_root . 'agents/README.md' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
$isudev_catalog = isudev_catalog_build( $isudev_root );

Checks::true(
	'agents catalog: agents/README.md is current — run `php tools/agents-catalog.php`',
	isudev_catalog_splice( $isudev_guide, $isudev_catalog['markdown'] ) === $isudev_guide
);

Checks::is( 'agents catalog: every hook is documented in a shipped README or guide', $isudev_catalog['undocumented'], array() );

$isudev_vendor = file_get_contents( $isudev_root . 'bin/agents-init.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
Checks::true( 'agents catalog: bin/agents-init.php exists', false !== $isudev_vendor );
