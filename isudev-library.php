<?php
/**
 * Plugin Name:       IsuDev Library
 * Plugin URI:        https://isudev.pl
 * Description:       Reusable Gutenberg blocks and tools by IsuDev. Server-rendered, modular, toggleable from the admin.
 * Version:           1.0.0
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

defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.0';

define( 'IsuDevLibrary\\PATH', plugin_dir_path( __FILE__ ) );
define( 'IsuDevLibrary\\URL', plugin_dir_url( __FILE__ ) );

require_once PATH . 'includes/utils/array.php';
require_once PATH . 'includes/utils/icon.php';
require_once PATH . 'includes/config.php';
require_once PATH . 'includes/variations.php';
require_once PATH . 'includes/class-registry.php';
require_once PATH . 'includes/class-loader.php';

Loader::boot();

/**
 * Load the plugin text domain for PHP translations.
 *
 * @return void
 */
function load_textdomain(): void {
	load_plugin_textdomain( 'isudev-library', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\\load_textdomain' );
