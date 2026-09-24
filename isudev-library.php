<?php
/**
 * Plugin Name:       IsuDev Library
 * Plugin URI:        https://isudev.pl
 * Description:       Reusable Gutenberg blocks and tools by IsuDev. Server-rendered, modular, toggleable from the admin.
 * Version:           1.13.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            IsuDev
 * Author URI:        https://isudev.pl
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       isudev-library
 * Domain Path:       /languages
 * Update URI:        false
 *
 * @package IsuDevLibrary
 */

declare( strict_types = 1 );

namespace IsuDevLibrary;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.13.0';

define( 'IsuDevLibrary\\PATH', plugin_dir_path( __FILE__ ) );
define( 'IsuDevLibrary\\URL', plugin_dir_url( __FILE__ ) );

$isudev_library_autoload = PATH . 'vendor/autoload.php';

// A bundled vendor/ means this is a release-zip install. Composer-managed sites have none, and
// there Composer — not the update checker — owns the version.
$isudev_library_self_updates = is_readable( $isudev_library_autoload );

if ( $isudev_library_self_updates ) {
	require_once $isudev_library_autoload;
}

// Update checks only matter in wp-admin and during cron, so the front end stays untouched.
if ( $isudev_library_self_updates && class_exists( PucFactory::class ) && ( is_admin() || wp_doing_cron() ) ) {
	$isudev_library_updater = PucFactory::buildUpdateChecker(
		'https://github.com/IsuDevelopment/isudev-library/',
		__FILE__,
		'isudev-library'
	);
	$isudev_library_vcs_api = $isudev_library_updater->getVcsApi();

	// The built zip is a release asset, not the GitHub source archive. Checked by method instead of
	// class, because PUC exposes its API classes under a version-specific namespace.
	if ( method_exists( $isudev_library_vcs_api, 'enableReleaseAssets' ) ) {
		$isudev_library_vcs_api->enableReleaseAssets();
	}
}

require_once PATH . 'includes/utils/array.php';
require_once PATH . 'includes/utils/icon.php';
require_once PATH . 'includes/config.php';
require_once PATH . 'includes/variations.php';
require_once PATH . 'includes/class-registry.php';
require_once PATH . 'includes/class-loader.php';
require_once PATH . 'includes/class-extensions.php';
require_once PATH . 'includes/settings.php';
require_once PATH . 'includes/admin.php';
require_once PATH . 'includes/rest.php';
require_once PATH . 'includes/google-reviews/repository.php';
require_once PATH . 'includes/google-reviews/render-helpers.php';
require_once PATH . 'includes/google-reviews/rest.php';

Loader::boot();
Extensions::boot();
Settings\boot();
Admin\boot();
REST\boot();
GoogleReviews\Rest\boot();
Utils\boot_icons();
Config\boot();

/**
 * Load the plugin text domain for PHP translations.
 *
 * @return void
 */
function load_textdomain(): void {
	load_plugin_textdomain( 'isudev-library', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\\load_textdomain' );
