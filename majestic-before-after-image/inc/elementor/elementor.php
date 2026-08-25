<?php
/**
 * Elementor init
 *
 * @package MBAI
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Plugin;

/**
 * MBAI Elementor class.
 *
 * @since 1.0.0
 */
class MBAI_Elementor {
	/**
	 * Instance.
	 *
	 * @since 1.0.0
	 * @var MBAI_Elementor
	 */
	private static $instance = null;

	/**
	 * Return instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin An instance of MBAI_Elementor.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Register widgets.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Register widget categories.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_widget_category' ) );
	}

	/**
	 * Register widget categories.
	 *
	 * @since 1.0.0
	 *
	 * @param Elements_Manager $elements_manager Instance of Elements_Manager.
	 */
	public function register_widget_category( $elements_manager ) {
		$elements_manager->add_category(
			'mbai-widgets',
			array(
				'title' => esc_html__( 'Before After Image', 'majestic-before-after-image' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Include Widgets files
	 *
	 * Load widgets files
	 *
	 * @since 1.2.0
	 * @access private
	 */
	private function include_widgets_files() {
		require_once MBAI_DIR . '/inc/elementor/widget.php';
	}

	/**
	 * Register Widgets.
	 *
	 * Register new Elementor widgets.
	 *
	 * @since 1.2.0
	 * @access public
	 */
	public function register_widgets() {
		$this->include_widgets_files();

		Plugin::instance()->widgets_manager->register( new MBAI_Elementor_Widget() );
	}
}

MBAI_Elementor::instance();
