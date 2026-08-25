<?php
/**
 * Init
 *
 * @package MBAI
 */

defined( 'ABSPATH' ) || exit;

use Nilambar\AdminNotice\Notice;

/**
 * Main MBAI class.
 *
 * @since 1.0.0
 */
final class MBAI {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Load translations.
		add_action( 'init', array( $this, 'i18n' ) );

		// Bootstrap all integrations after plugins are loaded.
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );

		// Admin notice helper.
		add_action( 'admin_init', array( $this, 'nifty_cs_admin_notice' ) );

		// Plugin-list action links.
		add_filter( 'plugin_action_links_' . MBAI_BASE_FILENAME, array( $this, 'customize_plugin_action_links' ) );
	}

	/**
	 * Load textdomain.
	 *
	 * @since 1.0.0
	 */
	public function i18n() {
		load_plugin_textdomain( 'majestic-before-after-image' );
	}

	/**
	 * Bootstrap all plugin features.
	 *
	 * - Always:       admin page, shortcode, global asset registration.
	 * - Conditional:  Elementor widget (when Elementor is active).
	 * - Conditional:  WooCommerce integration (when WooCommerce is active).
	 *
	 * @since 3.0.0
	 */
	public function init_plugin() {
		// Admin welcome page (always).
		require_once MBAI_DIR . '/inc/admin-page/admin-page.php';

		// Register shared CSS + JS assets (always, used by shortcode & Woo too).
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );

		// Shortcode – works without any page builder or WooCommerce.
		require_once MBAI_DIR . '/inc/shortcode/shortcode.php';

		// Elementor integration (only when active).
		if ( did_action( 'elementor/loaded' ) ) {
			require_once MBAI_DIR . '/inc/elementor/elementor.php';
		}

		// WooCommerce integration (only when active).
		if ( $this->is_woocommerce_active() ) {
			require_once MBAI_DIR . '/inc/woocommerce/woocommerce.php';
		}
	}

	/**
	 * Register plugin CSS and JS.
	 *
	 * These are registered globally so both the shortcode, the Elementor widget
	 * and the WooCommerce integration can enqueue them as needed.
	 *
	 * @since 3.0.0
	 */
	public function register_assets() {
		wp_register_style(
			'mbai-style',
			MBAI_URL . '/assets/css/mbai.css',
			array(),
			MBAI_VERSION
		);

		wp_register_script(
			'mbai-script',
			MBAI_URL . '/assets/js/mbai.js',
			array(),
			MBAI_VERSION,
			true
		);

		// Auto-enqueue on any front-end page that has a shortcode or Woo widget.
		// (Elementor widget handles its own enqueue via get_script_depends /
		// get_style_depends, so we only need to force-enqueue for shortcode/Woo.)
		if ( ! is_admin() ) {
			wp_enqueue_style( 'mbai-style' );
			wp_enqueue_script( 'mbai-script' );
		}
	}

	/**
	 * Check whether WooCommerce is active.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	private function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Customize plugin action links.
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions Action links.
	 * @return array Modified action links.
	 */
	public function customize_plugin_action_links( $actions ) {
		// Page is registered as a top-level menu, so it lives under admin.php.
		$url = add_query_arg(
			array( 'page' => 'majestic-before-after-image' ),
			admin_url( 'admin.php' )
		);

		$actions = array_merge(
			array(
				'settings' => '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'majestic-before-after-image' ) . '</a>',
			),
			$actions
		);

		return $actions;
	}

	/**
	 * Register admin notice.
	 *
	 * @since 1.0.0
	 */
	public function nifty_cs_admin_notice() {
		Notice::init(
			array(
				'slug' => MBAI_SLUG,
				'name' => esc_html__( 'Before After Image', 'majestic-before-after-image' ),
			)
		);
	}
}

new MBAI();
