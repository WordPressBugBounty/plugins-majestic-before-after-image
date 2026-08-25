<?php
/**
 * Plugin Name: Before After Image
 * Plugin URI: https://wpplugin.org/before-after-image-slider/
 * Description: Before After Image with a draggable handle. Works standalone via shortcode, with the Elementor page builder, and with WooCommerce product pages.
 * Version: 3.0.2
 * Requires PHP: 5.6
 * Requires at least: 6.2
 * Author: Scott Paterson
 * Author URI: https://wpplugin.org
 * Text Domain: majestic-before-after-image
 * Domain Path: /languages
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package MBAI
 */

defined( 'ABSPATH' ) || exit;

// Empty function used by the Pro version to detect that the free version is active.
function mbai_free() {}

// If the Pro version is active, deactivate it.
if ( function_exists( 'mbai_pro' ) ) {
	deactivate_plugins( 'majestic-before-after-image-pro/majestic-before-after-image.php' );
} else {

	define( 'MBAI_VERSION',       '3.0.2' );
	define( 'MBAI_SLUG',          'majestic-before-after-image' );
	define( 'MBAI_BASE_NAME',     basename( __DIR__ ) );
	define( 'MBAI_BASE_FILEPATH', __FILE__ );
	define( 'MBAI_BASE_FILENAME', plugin_basename( __FILE__ ) );
	define( 'MBAI_DIR',           rtrim( plugin_dir_path( __FILE__ ), '/' ) );
	define( 'MBAI_URL',           rtrim( plugin_dir_url( __FILE__ ),  '/' ) );

	if ( ! defined( 'WP_WELCOME_DIR' ) ) {
		define( 'WP_WELCOME_DIR', MBAI_DIR . '/vendor/ernilambar/wp-welcome' );
	}

	if ( ! defined( 'WP_WELCOME_URL' ) ) {
		define( 'WP_WELCOME_URL', MBAI_URL . '/vendor/ernilambar/wp-welcome' );
	}

	// Init autoload (vendor packages for admin welcome page).
	if ( file_exists( MBAI_DIR . '/vendor/autoload.php' ) ) {
		require_once MBAI_DIR . '/vendor/autoload.php';
		require_once MBAI_DIR . '/vendor/ernilambar/wp-welcome/init.php';
	}

	// Init plugin.
	require_once MBAI_DIR . '/inc/mbai.php';

}
