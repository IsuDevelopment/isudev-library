<?php
/**
 * Checks for the pure parts of disable-emoji, reset-admin-dashboard and enqueue-theme-block-styles.
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

$isudev_ext = dirname( __DIR__, 2 ) . '/extensions/';

require_once $isudev_ext . 'disable-emoji/hooks.php';
require_once $isudev_ext . 'reset-admin-dashboard/hooks.php';
require_once $isudev_ext . 'enqueue-theme-block-styles/hooks.php';

use IsuDevLibrary\Extensions\DisableEmoji;
use IsuDevLibrary\Extensions\EnqueueThemeBlockStyles;
use IsuDevLibrary\Extensions\ResetAdminDashboard;

// disable-emoji.
Checks::is(
	'emoji: wpemoji TinyMCE plugin removed and list reindexed',
	DisableEmoji\remove_tinymce_plugin( array( 'charmap', 'wpemoji', 'wplink' ) ),
	array( 'charmap', 'wplink' )
);
Checks::is( 'emoji: non-array plugins', DisableEmoji\remove_tinymce_plugin( null ), array() );

// reset-admin-dashboard.
$isudev_widgets = ResetAdminDashboard\widgets();
Checks::true( 'dashboard: core widgets removed by default', in_array( 'dashboard_activity', $isudev_widgets, true ) && in_array( 'dashboard_primary', $isudev_widgets, true ) );
Checks::is( 'dashboard: PHP nag kept', in_array( 'dashboard_php_nag', $isudev_widgets, true ), false );
Checks::is( 'dashboard: browser nag kept', in_array( 'dashboard_browser_nag', $isudev_widgets, true ), false );

// enqueue-theme-block-styles: sanitize_dir().
Checks::is( 'theme block styles dir: default for non-string', EnqueueThemeBlockStyles\sanitize_dir( null ), 'build/blocks' );
Checks::is( 'theme block styles dir: slashes trimmed', EnqueueThemeBlockStyles\sanitize_dir( '/dist/blocks/' ), 'dist/blocks' );
Checks::is( 'theme block styles dir: parent traversal refused', EnqueueThemeBlockStyles\sanitize_dir( 'build/../../etc' ), 'build/blocks' );
Checks::is( 'theme block styles dir: odd characters refused', EnqueueThemeBlockStyles\sanitize_dir( 'build/<x>' ), 'build/blocks' );

// handle().
Checks::is( 'theme block styles handle', EnqueueThemeBlockStyles\handle( 'core', 'button', false ), 'isudev-theme-block-styles-core-button' );
Checks::is( 'theme block styles handle: parent', EnqueueThemeBlockStyles\handle( 'gravityforms', 'form', true ), 'isudev-theme-block-styles-gravityforms-form-parent' );

// discover() against a throwaway tree.
$isudev_tmp = sys_get_temp_dir() . '/isudev-tbs-' . getmypid();
foreach ( array( 'core', 'isudev', 'Bad_NS' ) as $isudev_ns ) {
	@mkdir( $isudev_tmp . '/' . $isudev_ns, 0777, true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
}
foreach ( array( 'core/button.css', 'core/button-rtl.css', 'core/image.css', 'core/notes.txt', 'isudev/read-more.css', 'Bad_NS/x.css' ) as $isudev_file ) {
	file_put_contents( $isudev_tmp . '/' . $isudev_file, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
}
@mkdir( $isudev_tmp . '/core/dir.css' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

Checks::is(
	'theme block styles discover: css files only, rtl/invalid/dirs skipped, sorted',
	array_column( EnqueueThemeBlockStyles\discover( $isudev_tmp ), 'file' ),
	array( 'core/button.css', 'core/image.css', 'isudev/read-more.css' )
);
Checks::is( 'theme block styles discover: missing dir', EnqueueThemeBlockStyles\discover( $isudev_tmp . '/missing' ), array() );

foreach ( array( 'core/button.css', 'core/button-rtl.css', 'core/image.css', 'core/notes.txt', 'isudev/read-more.css', 'Bad_NS/x.css' ) as $isudev_file ) {
	unlink( $isudev_tmp . '/' . $isudev_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
}
foreach ( array( 'core/dir.css', 'core', 'isudev', 'Bad_NS', '' ) as $isudev_dir ) {
	rmdir( $isudev_tmp . '/' . $isudev_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
}
