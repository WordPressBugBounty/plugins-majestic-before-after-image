<?php
/**
 * Admin page
 *
 * @package MBAI
 */

defined( 'ABSPATH' ) || exit;

use Nilambar\Welcome\Welcome;

/**
 * MBAI admin page class.
 *
 * @since 1.0.0
 */
class MBAI_Admin_Page {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_welcome_init', array( $this, 'add_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media' ) );
	}

	/**
	 * Enqueue assets needed on our admin page.
	 *
	 * Loads the WordPress media library (for image pickers) plus the plugin's
	 * own front-end CSS/JS so the Shortcode tab can show a live preview.
	 *
	 * @since 3.0.0
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_media( $hook ) {
		// The page hook differs depending on menu placement:
		//  - top-level menu : toplevel_page_{slug}
		//  - submenu        : {parent}_page_{slug} (e.g. settings_page_{slug})
		// Match either so the assets always load on our page.
		if ( false === strpos( $hook, 'majestic-before-after-image' ) ) {
			return;
		}

		wp_enqueue_media();

		// Front-end assets for the live preview.
		wp_enqueue_style( 'mbai-style', MBAI_URL . '/assets/css/mbai.css', array(), MBAI_VERSION );
		wp_enqueue_script( 'mbai-script', MBAI_URL . '/assets/js/mbai.js', array(), MBAI_VERSION, true );
	}

	/**
	 * Register admin page.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_page() {
		$obj = new Welcome( 'plugin', 'majestic-before-after-image' );

		$obj->set_page(
			array(
				'page_title'     => esc_html__( 'Before After Image', 'majestic-before-after-image' ),
				/* translators: %s: version number */
				'page_subtitle'  => sprintf( esc_html__( 'Version %s', 'majestic-before-after-image' ), MBAI_VERSION ),
				'menu_title'     => esc_html__( 'Before After Image', 'majestic-before-after-image' ),
				'menu_slug'      => 'majestic-before-after-image',
				'capability'     => 'manage_options',
				'menu_icon'      => 'dashicons-image-flip-horizontal',
				'top_level_menu' => true,
			)
		);

		$obj->set_quick_links( array() );

		$obj->set_admin_notice(
			array(
				'screens' => array( 'dashboard' ),
			)
		);

		// Tab 1 – Getting Started.
		$obj->add_tab(
			array(
				'id'              => 'getting-started',
				'title'           => esc_html__( 'Getting Started', 'majestic-before-after-image' ),
				'type'            => 'custom',
				'render_callback' => array( $this, 'render_tab_getting_started' ),
			)
		);

		// Tab 2 – Shortcode Builder.
		$obj->add_tab(
			array(
				'id'              => 'shortcode',
				'title'           => esc_html__( 'Shortcode', 'majestic-before-after-image' ),
				'type'            => 'custom',
				'render_callback' => array( $this, 'render_tab_shortcode' ),
			)
		);

		// Tab 3 – Elementor.
		$obj->add_tab(
			array(
				'id'              => 'elementor',
				'title'           => esc_html__( 'Elementor', 'majestic-before-after-image' ),
				'type'            => 'custom',
				'render_callback' => array( $this, 'render_tab_elementor' ),
			)
		);

		// Tab 4 – WooCommerce.
		$obj->add_tab(
			array(
				'id'              => 'woocommerce',
				'title'           => esc_html__( 'WooCommerce', 'majestic-before-after-image' ),
				'type'            => 'custom',
				'render_callback' => array( $this, 'render_tab_woocommerce' ),
			)
		);

		// Tab 5 – More Plugins.
		$obj->add_tab(
			array(
				'id'              => 'more-plugins',
				'title'           => esc_html__( 'More Plugins', 'majestic-before-after-image' ),
				'type'            => 'custom',
				'render_callback' => array( $this, 'render_tab_more_plugins' ),
			)
		);

		$obj->run();
	}

	/**
	 * Shared inline styles used across all tabs.
	 *
	 * Only output once — guarded by a static flag.
	 *
	 * @since 3.0.0
	 */
	private function admin_styles() {
		static $printed = false;
		if ( $printed ) {
			return;
		}
		$printed = true;
		?>
		<style>
			/* ---- General doc layout ---- */
			.mbai-doc-section { margin: 0 0 30px; }
			.mbai-doc-section h2 { font-size: 1.3em; margin: 0 0 8px; padding-bottom: 6px; border-bottom: 1px solid #ddd; }
			.mbai-doc-section h3 { font-size: 1.05em; margin: 22px 0 6px; }
			.mbai-doc-section p  { margin: 0 0 10px; max-width: 780px; }
			.mbai-doc-section ol,
			.mbai-doc-section ul { margin: 0 0 10px 1.5em; max-width: 780px; }
			.mbai-doc-section li { margin-bottom: 5px; }

			/* ---- Options table ---- */
			.mbai-attr-table { border-collapse: collapse; width: 100%; max-width: 900px; margin: 10px 0 20px; font-size: 13px; }
			.mbai-attr-table th,
			.mbai-attr-table td { text-align: left; padding: 8px 12px; border: 1px solid #ddd; }
			.mbai-attr-table th { background: #f5f5f5; font-weight: 600; }
			.mbai-attr-table tr:nth-child(even) td { background: #fafafa; }
			.mbai-attr-table code { background: #f0f0f0; padding: 1px 5px; border-radius: 3px; }

			/* ---- Getting started cards ---- */
			.mbai-gs-cards { display: flex; gap: 20px; flex-wrap: wrap; margin: 20px 0 28px; }
			.mbai-gs-card { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 22px 24px; flex: 1; min-width: 200px; max-width: 280px; display: flex; flex-direction: column; }
			.mbai-gs-card .dashicons { font-size: 28px; width: 28px; height: 28px; color: #2271b1; margin-bottom: 10px; }
			.mbai-gs-card h3 { margin: 0 0 8px; font-size: 1.05em; }
			.mbai-gs-card p  { font-size: 13px; color: #555; margin: 0 0 14px; flex: 1; }
			.mbai-gs-card .button { display: inline-block; align-self: flex-start; }

			/* ---- Welcome banner ---- */
			.mbai-welcome-banner { background: #fff; border: 1px solid #ddd; color: #333; border-radius: 6px; padding: 28px 32px; margin-bottom: 28px; max-width: 860px; }
			.mbai-welcome-banner h2 { color: #333; font-size: 1.4em; margin: 0 0 8px; border: none; padding: 0; }
			.mbai-welcome-banner p  { color: #555; margin: 0; font-size: 14px; max-width: 100%; }

			/* ---- Shortcode builder ---- */
			.mbai-builder { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 24px; flex: 1; min-width: 0; margin-bottom: 28px; }
			.mbai-builder-layout { display: flex; gap: 24px; align-items: flex-start; width: 100%; max-width: 1400px; margin-bottom: 28px; }
			.mbai-builder-fields { }
			.mbai-builder-output { width: 320px; flex-shrink: 0; position: sticky; top: 32px; }
			.mbai-builder-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px 24px; margin-bottom: 20px; }
			.mbai-builder-field label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; }
			.mbai-builder-field .description { font-size: 12px; color: #777; margin-top: 3px; }
			.mbai-builder-field select,
			.mbai-builder-field input[type="text"],
			.mbai-builder-field input[type="number"] { width: 100%; }
			.mbai-builder-field .mbai-image-picker { display: flex; align-items: center; gap: 10px; }
			.mbai-builder-field .mbai-image-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 3px; border: 1px solid #ddd; display: none; }
			.mbai-builder-field .mbai-image-thumb.has-image { display: block; }
			.mbai-builder-field .mbai-clear-image { color: #a00; font-size: 12px; cursor: pointer; text-decoration: underline; margin-left: 4px; display: none; }
			.mbai-builder-field .mbai-clear-image.visible { display: inline; }
			.mbai-output-box { background: #f6f7f7; border: 1px solid #ddd; border-radius: 6px; padding: 16px; }
			.mbai-output-box-label { color: #555; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; margin: 0 0 10px; }
			.mbai-output-box code { display: block; color: #2271b1; font-family: monospace; font-size: 12px; word-break: break-all; white-space: normal; background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 10px; margin-bottom: 14px; line-height: 1.6; }
			.mbai-output-box code.mbai-shortcode-empty { color: #777; font-family: inherit; font-style: italic; }
			.mbai-copy-btn { width: 100%; text-align: center; }
			.mbai-copy-btn.copied { background: #4caf50 !important; border-color: #4caf50 !important; color: #fff !important; }
			.mbai-builder-section-title { font-size: 13px; font-weight: 600; color: #444; text-transform: uppercase; letter-spacing: .05em; margin: 0 0 12px; padding-bottom: 6px; border-bottom: 1px solid #eee; }
			/* ---- Live preview ---- */
			.mbai-preview-box { margin-top: 18px; }
			.mbai-preview-inner { background: #f6f7f7; border: 1px solid #ddd; border-radius: 6px; padding: 16px; min-height: 80px; }
			.mbai-preview-empty { color: #888; font-size: 13px; font-style: italic; text-align: center; padding: 24px 0; }
			.mbai-preview-inner .mbai-before-after-wrap { line-height: 0; }
			/* Narrow screens: output moves above fields */
			@media ( max-width: 900px ) {
				.mbai-builder-layout { flex-direction: column; }
				.mbai-builder-output { width: 100%; position: static; order: -1; }
				.mbai-copy-btn { width: auto; }
			}
		</style>
		<?php
	}

	/**
	 * Render the Getting Started tab.
	 *
	 * @since 3.0.0
	 */
	public function render_tab_getting_started() {
		$this->admin_styles();
		?>
		<style>
			/* ---- Getting Started page layout with Pro sidebar ---- */
			.mbai-gs-page-wrap { display: flex; gap: 28px; align-items: flex-start; }
			.mbai-gs-main { flex: 1; min-width: 0; }
			.mbai-gs-pro-sidebar {
				width: 240px;
				flex-shrink: 0;
				background: #fff;
				border: 1px solid #ddd;
				border-top: 6px solid #2271b1;
				border-radius: 4px;
				padding: 20px 18px 22px;
				position: sticky;
				top: 32px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.08);
			}
			.mbai-gs-pro-sidebar h3 {
				font-size: 1.08em;
				margin: 0 0 14px;
				padding: 0;
				color: #1d2327;
				font-weight: 700;
				text-align: center;
			}
			.mbai-gs-pro-sidebar ul {
				list-style: none;
				margin: 0 0 16px;
				padding: 0;
			}
			.mbai-gs-pro-sidebar ul li {
				font-size: 14px;
				color: #3c434a;
				padding: 7px 0;
				border-bottom: 1px solid #f0f0f0;
				line-height: 1.5;
				display: flex;
				gap: 8px;
				align-items: flex-start;
			}
			.mbai-gs-pro-sidebar ul li:last-child { border-bottom: none; }
			.mbai-gs-pro-sidebar ul li .dashicons {
				color: #2271b1;
				font-size: 18px;
				width: 18px;
				height: 18px;
				flex-shrink: 0;
				margin-top: 1px;
			}
			.mbai-gs-pro-sidebar .mbai-pro-cta {
				display: block;
				text-align: center;
				font-size: 13px;
				font-weight: 600;
				padding: 8px 12px;
				text-decoration: none;
				margin-bottom: 10px;
			}
			.mbai-gs-pro-sidebar .mbai-pro-tagline {
				font-size: 11px;
				color: #777;
				text-align: center;
				margin: 0;
			}
		</style>

		<div class="mbai-gs-page-wrap">

			<div class="mbai-gs-main">

				<div class="mbai-welcome-banner">
					<h2><?php esc_html_e( 'Welcome to Before After Image!', 'majestic-before-after-image' ); ?></h2>
					<p><?php esc_html_e( 'Thank you for installing the plugin - we hope enjoy using it! The before/after slider is a great way to show transformations, product comparisons, renovations, photo edits, and much more.', 'majestic-before-after-image' ); ?></p>
				</div>

				<p><?php esc_html_e( 'This plugin can be used in multiple ways:', 'majestic-before-after-image' ); ?></p>

				<div class="mbai-gs-cards">
					<div class="mbai-gs-card">
						<span class="dashicons dashicons-editor-code"></span>
						<h3><?php esc_html_e( 'Shortcode', 'majestic-before-after-image' ); ?></h3>
						<p><?php esc_html_e( 'Use the shortcode in any post, page, or widget - no page builder needed.', 'majestic-before-after-image' ); ?></p>
						<a href="#" class="button button-secondary mbai-tab-link" data-tab="majestic-before-after-image-shortcode"><?php esc_html_e( 'Shortcode Builder', 'majestic-before-after-image' ); ?></a>
					</div>

					<div class="mbai-gs-card">
						<span class="dashicons dashicons-layout"></span>
						<h3><?php esc_html_e( 'Elementor Widget', 'majestic-before-after-image' ); ?></h3>
						<p><?php esc_html_e( 'Drag and drop the widget into any Elementor page. Full style controls included.', 'majestic-before-after-image' ); ?></p>
						<a href="#" class="button button-secondary mbai-tab-link" data-tab="majestic-before-after-image-elementor"><?php esc_html_e( 'How to use with Elementor', 'majestic-before-after-image' ); ?></a>
					</div>

					<div class="mbai-gs-card">
						<span class="dashicons dashicons-cart"></span>
						<h3><?php esc_html_e( 'WooCommerce', 'majestic-before-after-image' ); ?></h3>
						<p><?php esc_html_e( 'Add a before/after comparison to any product page directly from the product editor.', 'majestic-before-after-image' ); ?></p>
						<a href="#" class="button button-secondary mbai-tab-link" data-tab="majestic-before-after-image-woocommerce"><?php esc_html_e( 'How to use with WooCommerce', 'majestic-before-after-image' ); ?></a>
					</div>

					<div class="mbai-gs-card">
						<span class="dashicons dashicons-star-filled"></span>
						<h3><?php esc_html_e( 'Enjoying the plugin?', 'majestic-before-after-image' ); ?></h3>
						<p><?php esc_html_e( 'A quick review on WordPress.org means a lot and helps other people find the plugin.', 'majestic-before-after-image' ); ?></p>
						<a href="https://wordpress.org/support/plugin/majestic-before-after-image/reviews/#new-post" class="button button-secondary" target="_blank"><?php esc_html_e( 'Leave a Review', 'majestic-before-after-image' ); ?></a>
					</div>
				</div>

			</div><!-- .mbai-gs-main -->

			<div class="mbai-gs-pro-sidebar">
				<h3><?php esc_html_e( 'Pro Version Features', 'majestic-before-after-image' ); ?></h3>
				<ul>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Grab attention instantly with an auto-sliding handle — no click needed', 'majestic-before-after-image' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Show before, during, and after with 3-image comparison and two handles', 'majestic-before-after-image' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Let visitors zoom in with a full-screen view for a closer look', 'majestic-before-after-image' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Match your brand with custom colors for the line, circle, and arrows', 'majestic-before-after-image' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Polish the look with rounded corners — from subtle to fully rounded', 'majestic-before-after-image' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Save slider configs and reuse them anywhere with the Shortcode Manager', 'majestic-before-after-image' ); ?></li>
				</ul>
				<a href="https://wpplugin.org/downloads/before-after-image-pro/" class="mbai-pro-cta button button-primary" target="_blank">
					<?php esc_html_e( 'See Pro Version Demos', 'majestic-before-after-image' ); ?>
				</a>
				<p class="mbai-pro-tagline"><?php esc_html_e( '30-day money back guarantee', 'majestic-before-after-image' ); ?></p>
			</div><!-- .mbai-gs-pro-sidebar -->

		</div><!-- .mbai-gs-page-wrap -->

		<script>
		( function() {
			document.addEventListener( 'DOMContentLoaded', function() {
				var links = document.querySelectorAll( '.mbai-tab-link' );
				links.forEach( function( link ) {
					link.addEventListener( 'click', function( e ) {
						e.preventDefault();
						var targetId = this.getAttribute( 'data-tab' );
						var tabNav = document.querySelector( '.wpw-tabs-nav a[href="#' + targetId + '"]' );
						if ( tabNav ) {
							tabNav.click();
							window.scrollTo( { top: 0, behavior: 'smooth' } );
						}
					} );
				} );
			} );
		}() );
		</script>
		<?php
	}

	/**
	 * Render the Shortcode tab — visual builder + options table.
	 *
	 * @since 3.0.0
	 */
	public function render_tab_shortcode() {
		$this->admin_styles();
		?>
		<div class="mbai-doc-section">
			<h2><?php esc_html_e( 'Shortcode Builder', 'majestic-before-after-image' ); ?></h2>
			<p><?php esc_html_e( 'Use the options below to configure your slider, then copy the generated shortcode and paste it into any post, page, or text widget.', 'majestic-before-after-image' ); ?></p>
		</div>

		<div class="mbai-builder-layout">

		<div class="mbai-builder">
			<div class="mbai-builder-fields">

			<p class="mbai-builder-section-title"><?php esc_html_e( 'Images', 'majestic-before-after-image' ); ?></p>
			<div class="mbai-builder-grid">

				<div class="mbai-builder-field">
					<label for="mbai_before_image"><?php esc_html_e( 'Before Image', 'majestic-before-after-image' ); ?></label>
					<div class="mbai-image-picker">
						<img class="mbai-image-thumb" id="mbai_before_thumb" src="" alt="">
						<div>
							<button type="button" class="button mbai-pick-image" data-field="mbai_before_url" data-id="mbai_before_id" data-thumb="mbai_before_thumb" data-clear="mbai_before_clear">
								<?php esc_html_e( 'Select Image', 'majestic-before-after-image' ); ?>
							</button>
							<a href="#" class="mbai-clear-image" id="mbai_before_clear" data-field="mbai_before_url" data-id="mbai_before_id" data-thumb="mbai_before_thumb"><?php esc_html_e( 'Remove', 'majestic-before-after-image' ); ?></a>
						</div>
					</div>
					<input type="hidden" id="mbai_before_url" value="">
					<input type="hidden" id="mbai_before_id" value="">
					<p class="description"><?php esc_html_e( 'The left / top image', 'majestic-before-after-image' ); ?></p>
				</div>

				<div class="mbai-builder-field">
					<label for="mbai_after_image"><?php esc_html_e( 'After Image', 'majestic-before-after-image' ); ?></label>
					<div class="mbai-image-picker">
						<img class="mbai-image-thumb" id="mbai_after_thumb" src="" alt="">
						<div>
							<button type="button" class="button mbai-pick-image" data-field="mbai_after_url" data-id="mbai_after_id" data-thumb="mbai_after_thumb" data-clear="mbai_after_clear">
								<?php esc_html_e( 'Select Image', 'majestic-before-after-image' ); ?>
							</button>
							<a href="#" class="mbai-clear-image" id="mbai_after_clear" data-field="mbai_after_url" data-id="mbai_after_id" data-thumb="mbai_after_thumb"><?php esc_html_e( 'Remove', 'majestic-before-after-image' ); ?></a>
						</div>
					</div>
					<input type="hidden" id="mbai_after_url" value="">
					<input type="hidden" id="mbai_after_id" value="">
					<p class="description"><?php esc_html_e( 'The right / bottom image', 'majestic-before-after-image' ); ?></p>
				</div>

				<div class="mbai-builder-field">
					<label for="mbai_image_size"><?php esc_html_e( 'Image Size', 'majestic-before-after-image' ); ?></label>
					<select id="mbai_image_size" class="mbai-builder-input">
						<option value="thumbnail">Thumbnail</option>
						<option value="medium">Medium</option>
						<option value="medium_large">Medium Large</option>
						<option value="large" selected>Large</option>
						<option value="full">Full</option>
					</select>
					<p class="description"><?php esc_html_e( 'Which size to load from the media library', 'majestic-before-after-image' ); ?></p>
				</div>

				<div class="mbai-builder-field">
					<label for="mbai_align"><?php esc_html_e( 'Alignment', 'majestic-before-after-image' ); ?></label>
					<select id="mbai_align" class="mbai-builder-input">
						<option value=""><?php esc_html_e( 'None (default — full width)', 'majestic-before-after-image' ); ?></option>
						<option value="left"><?php esc_html_e( 'Left', 'majestic-before-after-image' ); ?></option>
						<option value="center"><?php esc_html_e( 'Center', 'majestic-before-after-image' ); ?></option>
						<option value="right"><?php esc_html_e( 'Right', 'majestic-before-after-image' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Float the slider left, center, or right', 'majestic-before-after-image' ); ?></p>
				</div>

			</div><!-- .mbai-builder-grid -->

			<p class="mbai-builder-section-title"><?php esc_html_e( 'Labels', 'majestic-before-after-image' ); ?></p>
			<div class="mbai-builder-grid">

				<div class="mbai-builder-field">
					<label for="mbai_before_label"><?php esc_html_e( 'Before Label', 'majestic-before-after-image' ); ?></label>
					<input type="text" id="mbai_before_label" value="Before" class="mbai-builder-input">
					<p class="description"><?php esc_html_e( 'Text shown over the before image', 'majestic-before-after-image' ); ?></p>
				</div>

				<div class="mbai-builder-field">
					<label for="mbai_after_label"><?php esc_html_e( 'After Label', 'majestic-before-after-image' ); ?></label>
					<input type="text" id="mbai_after_label" value="After" class="mbai-builder-input">
					<p class="description"><?php esc_html_e( 'Text shown over the after image', 'majestic-before-after-image' ); ?></p>
				</div>

				<div class="mbai-builder-field">
					<label for="mbai_labels_status"><?php esc_html_e( 'Labels Visibility', 'majestic-before-after-image' ); ?></label>
					<select id="mbai_labels_status" class="mbai-builder-input">
						<option value="hover"><?php esc_html_e( 'On Hover (default)', 'majestic-before-after-image' ); ?></option>
						<option value="always"><?php esc_html_e( 'Always visible', 'majestic-before-after-image' ); ?></option>
						<option value="never"><?php esc_html_e( 'Never show', 'majestic-before-after-image' ); ?></option>
					</select>
				</div>

			</div>

			<p class="mbai-builder-section-title"><?php esc_html_e( 'Handle', 'majestic-before-after-image' ); ?></p>
			<div class="mbai-builder-grid">

				<div class="mbai-builder-field">
					<label for="mbai_handle_type"><?php esc_html_e( 'Handle Type', 'majestic-before-after-image' ); ?></label>
					<select id="mbai_handle_type" class="mbai-builder-input">
						<option value="arrows"><?php esc_html_e( 'Arrows (default)', 'majestic-before-after-image' ); ?></option>
						<option value="text"><?php esc_html_e( 'Text', 'majestic-before-after-image' ); ?></option>
					</select>
				</div>

				<div class="mbai-builder-field" id="mbai_handle_label_wrap">
					<label for="mbai_handle_label"><?php esc_html_e( 'Handle Text', 'majestic-before-after-image' ); ?></label>
					<input type="text" id="mbai_handle_label" value="Drag" class="mbai-builder-input">
					<p class="description"><?php esc_html_e( 'Shown on the handle when type is "Text"', 'majestic-before-after-image' ); ?></p>
				</div>

				<div class="mbai-builder-field" id="mbai_handle_style_wrap">
					<label for="mbai_handle_style"><?php esc_html_e( 'Handle Style', 'majestic-before-after-image' ); ?></label>
					<select id="mbai_handle_style" class="mbai-builder-input">
						<option value="1"><?php esc_html_e( 'Style 1 — Round, white border', 'majestic-before-after-image' ); ?></option>
						<option value="2"><?php esc_html_e( 'Style 2 — Round, white fill', 'majestic-before-after-image' ); ?></option>
						<option value="3"><?php esc_html_e( 'Style 3 — Square, white border', 'majestic-before-after-image' ); ?></option>
						<option value="4"><?php esc_html_e( 'Style 4 — Square, white fill', 'majestic-before-after-image' ); ?></option>
						<option value="5"><?php esc_html_e( 'Style 5 — Tall round', 'majestic-before-after-image' ); ?></option>
						<option value="6"><?php esc_html_e( 'Style 6 — Tall round, white fill', 'majestic-before-after-image' ); ?></option>
					</select>
				</div>

				<div class="mbai-builder-field">
					<label for="mbai_color"><?php esc_html_e( 'Slider Color', 'majestic-before-after-image' ); ?></label>
					<select id="mbai_color" class="mbai-builder-input">
						<option value="white"><?php esc_html_e( 'White (default)', 'majestic-before-after-image' ); ?></option>
						<option value="black"><?php esc_html_e( 'Black', 'majestic-before-after-image' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Colour of the line, handle and arrows. Text handles flip for contrast.', 'majestic-before-after-image' ); ?></p>
				</div>

			</div>

			<p class="mbai-builder-section-title"><?php esc_html_e( 'Behaviour', 'majestic-before-after-image' ); ?></p>
			<div class="mbai-builder-grid">

				<div class="mbai-builder-field">
					<label for="mbai_orientation"><?php esc_html_e( 'Orientation', 'majestic-before-after-image' ); ?></label>
					<select id="mbai_orientation" class="mbai-builder-input">
						<option value="horizontal"><?php esc_html_e( 'Horizontal — slide left / right', 'majestic-before-after-image' ); ?></option>
						<option value="vertical"><?php esc_html_e( 'Vertical — slide up / down', 'majestic-before-after-image' ); ?></option>
					</select>
				</div>

				<div class="mbai-builder-field">
					<label for="mbai_default_offset"><?php esc_html_e( 'Starting Position', 'majestic-before-after-image' ); ?></label>
					<select id="mbai_default_offset" class="mbai-builder-input">
						<option value="0.1">10% <?php esc_html_e( '— mostly after', 'majestic-before-after-image' ); ?></option>
						<option value="0.2">20%</option>
						<option value="0.3">30%</option>
						<option value="0.4">40%</option>
						<option value="0.5" selected><?php esc_html_e( '50% — centre (default)', 'majestic-before-after-image' ); ?></option>
						<option value="0.6">60%</option>
						<option value="0.7">70%</option>
						<option value="0.8">80%</option>
						<option value="0.9">90% <?php esc_html_e( '— mostly before', 'majestic-before-after-image' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Where the handle sits when the page loads', 'majestic-before-after-image' ); ?></p>
				</div>

				<div class="mbai-builder-field">
					<label for="mbai_overlay"><?php esc_html_e( 'Overlay on Hover', 'majestic-before-after-image' ); ?></label>
					<select id="mbai_overlay" class="mbai-builder-input">
						<option value="no"><?php esc_html_e( 'No — off (default)', 'majestic-before-after-image' ); ?></option>
						<option value="yes"><?php esc_html_e( 'Yes — show dark overlay on hover', 'majestic-before-after-image' ); ?></option>
					</select>
				</div>

				<div class="mbai-builder-field">
					<label for="mbai_hover_move"><?php esc_html_e( 'Move on Hover', 'majestic-before-after-image' ); ?></label>
					<select id="mbai_hover_move" class="mbai-builder-input">
						<option value="no"><?php esc_html_e( 'No — drag to move (default)', 'majestic-before-after-image' ); ?></option>
						<option value="yes"><?php esc_html_e( 'Yes — handle follows mouse', 'majestic-before-after-image' ); ?></option>
					</select>
				</div>

			</div>

			</div><!-- .mbai-builder-fields -->
		</div><!-- .mbai-builder -->

			<div class="mbai-builder-output">
				<div class="mbai-output-box">
					<p class="mbai-output-box-label"><?php esc_html_e( 'Your Shortcode', 'majestic-before-after-image' ); ?></p>
					<code id="mbai_shortcode_output" class="mbai-shortcode-empty"><?php esc_html_e( 'Select a Before and After image to generate your shortcode.', 'majestic-before-after-image' ); ?></code>
					<button type="button" class="button button-primary mbai-copy-btn" id="mbai_copy_btn" disabled>
						<?php esc_html_e( 'Copy Shortcode', 'majestic-before-after-image' ); ?>
					</button>
				</div>

				<div class="mbai-preview-box">
					<p class="mbai-output-box-label"><?php esc_html_e( 'Live Preview', 'majestic-before-after-image' ); ?></p>
					<div class="mbai-preview-inner" id="mbai_preview">
						<div class="mbai-preview-empty"><?php esc_html_e( 'Select a Before and After image to see a live preview.', 'majestic-before-after-image' ); ?></div>
					</div>
				</div>
			</div><!-- .mbai-builder-output -->

		</div><!-- .mbai-builder-layout -->

		<script>
		( function() {
			/* ---- helpers ---- */
			function val( id ) {
				var el = document.getElementById( id );
				return el ? el.value : '';
			}

			/* ---- build shortcode string ---- */
			function buildShortcode() {
				var beforeId    = val( 'mbai_before_id' );
				var beforeUrl   = val( 'mbai_before_url' );
				var afterId     = val( 'mbai_after_id' );
				var afterUrl    = val( 'mbai_after_url' );
				var beforeLabel = val( 'mbai_before_label' );
				var afterLabel  = val( 'mbai_after_label' );
				var labelsStatus = val( 'mbai_labels_status' );
				var handleType  = val( 'mbai_handle_type' );
				var handleLabel = val( 'mbai_handle_label' );
				var handleStyle = val( 'mbai_handle_style' );
				var orientation = val( 'mbai_orientation' );
				var offset      = val( 'mbai_default_offset' );
				var overlay     = val( 'mbai_overlay' );
				var hoverMove   = val( 'mbai_hover_move' );
				var imageSize   = val( 'mbai_image_size' );
				var align       = val( 'mbai_align' );
				var color       = val( 'mbai_color' );

				// Defaults — only include an attribute when it differs.
				var defaults = {
					beforeLabel:  'Before',
					afterLabel:   'After',
					labelsStatus: 'hover',
					handleType:   'arrows',
					handleLabel:  'Drag',
					handleStyle:  '1',
					orientation:  'horizontal',
					offset:       '0.5',
					overlay:      'no',
					hoverMove:    'no',
					imageSize:    'large',
					align:        '',
					color:        'white',
				};

				var sc = '[mbai_before_after_image';

				// Use attachment ID when available (enables image_size); fall back to URL.
				if ( beforeId ) {
					sc += ' before_id="' + beforeId + '"';
				} else {
					sc += ' before="' + beforeUrl + '"';
				}
				if ( afterId ) {
					sc += ' after_id="' + afterId + '"';
				} else {
					sc += ' after="' + afterUrl + '"';
				}

				// image_size only makes a difference when IDs are used.
				if ( ( beforeId || afterId ) && imageSize !== defaults.imageSize ) {
					sc += ' image_size="' + imageSize + '"';
				}

				// Alignment.
				if ( align && align !== defaults.align ) {
					sc += ' align="' + align + '"';
				}

				// Only append optional attributes when changed from default.
				if ( orientation !== defaults.orientation ) {
					sc += ' orientation="' + orientation + '"';
				}
				if ( beforeLabel !== defaults.beforeLabel ) {
					sc += ' before_label="' + beforeLabel + '"';
				}
				if ( afterLabel !== defaults.afterLabel ) {
					sc += ' after_label="' + afterLabel + '"';
				}
				if ( labelsStatus !== defaults.labelsStatus ) {
					sc += ' labels_status="' + labelsStatus + '"';
				}
				if ( handleType !== defaults.handleType ) {
					sc += ' handle_type="' + handleType + '"';
				}
				if ( handleType === 'text' && handleLabel !== defaults.handleLabel ) {
					sc += ' handle_label="' + handleLabel + '"';
				}
				if ( handleStyle !== defaults.handleStyle ) {
					sc += ' handle_style="' + handleStyle + '"';
				}
				if ( offset !== defaults.offset ) {
					sc += ' default_offset="' + offset + '"';
				}
				if ( overlay !== defaults.overlay ) {
					sc += ' overlay="' + overlay + '"';
				}
				if ( hoverMove !== defaults.hoverMove ) {
					sc += ' hover_move="' + hoverMove + '"';
				}
				if ( color !== defaults.color ) {
					sc += ' color="' + color + '"';
				}

				sc += ']';
				return sc;
			}

			function updateOutput() {
				var out     = document.getElementById( 'mbai_shortcode_output' );
				var copyBtn = document.getElementById( 'mbai_copy_btn' );

				var hasBefore = val( 'mbai_before_id' ) || val( 'mbai_before_url' );
				var hasAfter  = val( 'mbai_after_id' )  || val( 'mbai_after_url' );

				if ( out ) {
					if ( hasBefore && hasAfter ) {
						out.textContent = buildShortcode();
						out.classList.remove( 'mbai-shortcode-empty' );
						if ( copyBtn ) { copyBtn.disabled = false; }
					} else {
						out.textContent = '<?php echo esc_js( __( 'Select a Before and After image to generate your shortcode.', 'majestic-before-after-image' ) ); ?>';
						out.classList.add( 'mbai-shortcode-empty' );
						if ( copyBtn ) { copyBtn.disabled = true; }
					}
				}

				updatePreview();
			}

			/* ---- live preview ---- */
			var previewSlider = null;

			function updatePreview() {
				var box = document.getElementById( 'mbai_preview' );
				if ( ! box ) { return; }

				var imageSize = val( 'mbai_image_size' );

				// Resolve the URL for the chosen image size when the attachment
				// exposes that size; otherwise fall back to the stored URL.
				function sizedUrl( fieldId ) {
					var el = document.getElementById( fieldId );
					if ( ! el || ! el.value ) { return ''; }
					var raw = el.getAttribute( 'data-sizes' );
					if ( raw ) {
						try {
							var map = JSON.parse( raw );
							if ( map[ imageSize ] ) { return map[ imageSize ]; }
						} catch ( e ) {}
					}
					return el.value;
				}

				var beforeUrl = sizedUrl( 'mbai_before_url' );
				var afterUrl  = sizedUrl( 'mbai_after_url' );

				// Tear down any previous slider instance to avoid leaking listeners.
				if ( previewSlider && typeof previewSlider.destroy === 'function' ) {
					previewSlider.destroy();
					previewSlider = null;
				}

				// Need both images to render a preview.
				if ( ! beforeUrl || ! afterUrl ) {
					box.className = 'mbai-preview-inner';
					box.innerHTML = '<div class="mbai-preview-empty"><?php echo esc_js( __( 'Select a Before and After image to see a live preview.', 'majestic-before-after-image' ) ); ?></div>';
					return;
				}

				var handleType = val( 'mbai_handle_type' );
				var handleStyle = val( 'mbai_handle_style' );
				var align = val( 'mbai_align' );
				var color = val( 'mbai_color' ) || 'white';

				var data = {
					orientation:          val( 'mbai_orientation' ),
					labels_status:        val( 'mbai_labels_status' ),
					before_label:         val( 'mbai_before_label' ),
					after_label:          val( 'mbai_after_label' ),
					handle_type:          handleType,
					handle_label:         val( 'mbai_handle_label' ),
					handle_offset:        parseFloat( val( 'mbai_default_offset' ) ),
					overlay_status:       ( val( 'mbai_overlay' ) === 'yes' ),
					move_slider_on_hover: ( val( 'mbai_hover_move' ) === 'yes' )
				};

				// Build the same markup the shortcode outputs.
				var wrap = document.createElement( 'div' );
				wrap.className = 'mbai-before-after-wrap handle-type-' + handleType + ' handle-style-' + handleStyle + ' mbai-color-' + color;
				wrap.setAttribute( 'data-mbai', JSON.stringify( data ) );

				var inner = document.createElement( 'div' );
				inner.className = 'mbai-before-after-container';

				var imgBefore = document.createElement( 'img' );
				imgBefore.className = 'img-before';
				imgBefore.src = beforeUrl;
				imgBefore.alt = data.before_label;

				var imgAfter = document.createElement( 'img' );
				imgAfter.className = 'img-after';
				imgAfter.src = afterUrl;
				imgAfter.alt = data.after_label;

				inner.appendChild( imgBefore );
				inner.appendChild( imgAfter );
				wrap.appendChild( inner );

				box.innerHTML = '';

				// Apply alignment wrapper if set.
				if ( align ) {
					box.className = 'mbai-preview-inner mbai-align-wrap mbai-align-' + align;
				} else {
					box.className = 'mbai-preview-inner';
				}

				box.appendChild( wrap );

				// Initialise the slider engine on the new markup.
				if ( typeof window.mbaiInitAll === 'function' ) {
					var instances = window.mbaiInitAll( box );
					if ( instances && instances.length ) {
						previewSlider = instances[0];
					}
				}
			}

			/* ---- show/hide handle label field ---- */
			function toggleHandleLabel() {
				var isText = ( val( 'mbai_handle_type' ) === 'text' );

				var labelWrap = document.getElementById( 'mbai_handle_label_wrap' );
				if ( labelWrap ) {
					labelWrap.style.display = isText ? '' : 'none';
				}

				// Handle Style only applies to the arrows handle.
				var styleWrap = document.getElementById( 'mbai_handle_style_wrap' );
				if ( styleWrap ) {
					styleWrap.style.display = isText ? 'none' : '';
				}
			}

			/* ---- wire up all builder inputs ---- */
			document.addEventListener( 'DOMContentLoaded', function() {
				toggleHandleLabel();

				var inputs = document.querySelectorAll( '.mbai-builder-input' );
				inputs.forEach( function( input ) {
					input.addEventListener( 'change', function() {
						if ( input.id === 'mbai_handle_type' ) { toggleHandleLabel(); }
						updateOutput();
					} );
					input.addEventListener( 'input', updateOutput );
				} );

				/* ---- WordPress media picker ---- */
				var pickBtns = document.querySelectorAll( '.mbai-pick-image' );
				pickBtns.forEach( function( btn ) {
					btn.addEventListener( 'click', function( e ) {
						e.preventDefault();
						var fieldId = btn.getAttribute( 'data-field' );
						var idField = btn.getAttribute( 'data-id' );
						var thumbId = btn.getAttribute( 'data-thumb' );
						var clearId = btn.getAttribute( 'data-clear' );

						if ( typeof wp === 'undefined' || ! wp.media ) { return; }

						var frame = wp.media( {
							title:    '<?php echo esc_js( __( 'Select Image', 'majestic-before-after-image' ) ); ?>',
							button:   { text: '<?php echo esc_js( __( 'Use This Image', 'majestic-before-after-image' ) ); ?>' },
							multiple: false,
						} );

						frame.on( 'select', function() {
							var attachment = frame.state().get( 'selection' ).first().toJSON();
							var url   = attachment.url;
							var thumb = ( attachment.sizes && attachment.sizes.thumbnail ) ? attachment.sizes.thumbnail.url : url;

							var fieldEl = document.getElementById( fieldId );
							var idEl    = document.getElementById( idField );
							var thumbEl = document.getElementById( thumbId );
							var clearEl = document.getElementById( clearId );

							if ( fieldEl ) { fieldEl.value = url; }
							if ( idEl )    { idEl.value    = attachment.id; }
							if ( thumbEl ) { thumbEl.src = thumb; thumbEl.classList.add( 'has-image' ); }
							if ( clearEl ) { clearEl.classList.add( 'visible' ); }

							// Store all available size URLs so the preview can switch sizes.
							if ( fieldEl && attachment.sizes ) {
								var sizeMap = {};
								Object.keys( attachment.sizes ).forEach( function( key ) {
									sizeMap[ key ] = attachment.sizes[ key ].url;
								} );
								sizeMap.full = attachment.url;
								fieldEl.setAttribute( 'data-sizes', JSON.stringify( sizeMap ) );
							}

							updateOutput();
						} );

						frame.open();
					} );
				} );

				/* ---- Clear image buttons ---- */
				var clearBtns = document.querySelectorAll( '.mbai-clear-image' );
				clearBtns.forEach( function( btn ) {
					btn.addEventListener( 'click', function( e ) {
						e.preventDefault();
						var fieldId = btn.getAttribute( 'data-field' );
						var idField = btn.getAttribute( 'data-id' );
						var thumbId = btn.getAttribute( 'data-thumb' );

						var fieldEl = document.getElementById( fieldId );
						var idEl    = document.getElementById( idField );
						var thumbEl = document.getElementById( thumbId );

						if ( fieldEl ) { fieldEl.value = ''; fieldEl.removeAttribute( 'data-sizes' ); }
						if ( idEl )    { idEl.value    = ''; }
						if ( thumbEl ) { thumbEl.src = ''; thumbEl.classList.remove( 'has-image' ); }
						btn.classList.remove( 'visible' );

						updateOutput();
					} );
				} );

				/* ---- Copy button ---- */
				var copyBtn = document.getElementById( 'mbai_copy_btn' );
				if ( copyBtn ) {
					copyBtn.addEventListener( 'click', function() {
						var text = buildShortcode();
						if ( navigator.clipboard && navigator.clipboard.writeText ) {
							navigator.clipboard.writeText( text );
						} else {
							var ta = document.createElement( 'textarea' );
							ta.value = text;
							ta.style.position = 'fixed';
							ta.style.opacity  = '0';
							document.body.appendChild( ta );
							ta.select();
							document.execCommand( 'copy' );
							document.body.removeChild( ta );
						}
						copyBtn.textContent = '<?php echo esc_js( __( 'Copied!', 'majestic-before-after-image' ) ); ?>';
						copyBtn.classList.add( 'copied' );
						setTimeout( function() {
							copyBtn.textContent = '<?php echo esc_js( __( 'Copy', 'majestic-before-after-image' ) ); ?>';
							copyBtn.classList.remove( 'copied' );
						}, 2000 );
					} );
				}

				updateOutput();
			} );
		}() );
		</script>
		<?php
	}

	/**
	 * Render the Elementor tab content.
	 *
	 * @since 3.0.0
	 */
	public function render_tab_elementor() {
		$this->admin_styles();
		?>
		<div class="mbai-doc-section">
			<h2><?php esc_html_e( 'Using the Elementor Widget', 'majestic-before-after-image' ); ?></h2>
			<p><?php esc_html_e( 'The Elementor widget is available automatically when Elementor is active. You get the same drag-and-drop experience as any other Elementor widget, with live style controls built in.', 'majestic-before-after-image' ); ?></p>
		</div>

		<div class="mbai-doc-section">
			<h3><?php esc_html_e( 'How to add the widget', 'majestic-before-after-image' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Open any page in the Elementor editor.', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'In the widget panel on the left, search for "Before After".', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Drag the "Before After Image" widget onto the page.', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'In the Content tab, upload or choose your Before and After images.', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Adjust the handle type, orientation, labels, and offset to your liking.', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Switch to the Style tab to customise colours, typography, and border radius.', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Click Publish or Update.', 'majestic-before-after-image' ); ?></li>
			</ol>
		</div>

		<div class="mbai-doc-section">
			<h3><?php esc_html_e( 'Content Tab Options', 'majestic-before-after-image' ); ?></h3>
			<table class="mbai-attr-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Option', 'majestic-before-after-image' ); ?></th>
						<th><?php esc_html_e( 'What it does', 'majestic-before-after-image' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr><td><?php esc_html_e( 'Before Image', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'The image shown on the left (or top in vertical mode)', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'After Image', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'The image shown on the right (or bottom in vertical mode)', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Image Size', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Which registered WordPress image size to load', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Enable Overlay', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Show a dark tint over the images on hover', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Enable Labels', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Show labels: On Hover, Always, or Never', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Before / After Text', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'The text inside each label badge', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Orientation', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Horizontal (left/right) or Vertical (up/down)', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Handle Type', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Arrows (default) or Text (shows custom text on the handle button)', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Handle Style', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Six visual handle presets (round, square, tall, etc.)', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Default Offset', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Starting position of the handle — 0.1 (left/top) to 1.0 (right/bottom)', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Enable On Mouse Hover', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'When on, the handle follows the mouse instead of requiring a drag', 'majestic-before-after-image' ); ?></td></tr>
				</tbody>
			</table>
		</div>

		<div class="mbai-doc-section">
			<h3><?php esc_html_e( 'Style Tab Options', 'majestic-before-after-image' ); ?></h3>
			<table class="mbai-attr-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Section', 'majestic-before-after-image' ); ?></th>
						<th><?php esc_html_e( 'What you can control', 'majestic-before-after-image' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr><td><?php esc_html_e( 'Labels', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Font, size, colour, background, border colour, border width, border radius, padding', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Handle', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Handle border and arrow colour', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Handle Text', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Font, size, text colour, background colour (only when Handle Type is "Text")', 'majestic-before-after-image' ); ?></td></tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the WooCommerce tab content.
	 *
	 * @since 3.0.0
	 */
	public function render_tab_woocommerce() {
		$this->admin_styles();
		?>
		<div class="mbai-doc-section">
			<h2><?php esc_html_e( 'Using the WooCommerce Integration', 'majestic-before-after-image' ); ?></h2>
			<p><?php esc_html_e( 'When WooCommerce is active, each product gets a Before After Image meta box. The slider is displayed below the product description on the single product page.', 'majestic-before-after-image' ); ?></p>
		</div>

		<div class="mbai-doc-section">
			<h3><?php esc_html_e( 'How to set it up', 'majestic-before-after-image' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Go to Products in your WordPress admin and open any product.', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Scroll down to the "Before After Image" meta box (below the product description).', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Tick "Enable before / after image on product page".', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Choose a Display Location — in place of the product image, as a gallery image within the existing gallery, or below the product summary.', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Click "Select Image" to choose a Before image from the media library.', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Click "Select Image" again to choose an After image.', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Fill in the Before and After label text (optional — defaults to "Before" / "After").', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Choose orientation, handle type, and label visibility.', 'majestic-before-after-image' ); ?></li>
				<li><?php esc_html_e( 'Click Update / Publish.', 'majestic-before-after-image' ); ?></li>
			</ol>
		</div>

		<div class="mbai-doc-section">
			<h3><?php esc_html_e( 'Meta Box Options', 'majestic-before-after-image' ); ?></h3>
			<table class="mbai-attr-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Option', 'majestic-before-after-image' ); ?></th>
						<th><?php esc_html_e( 'What it does', 'majestic-before-after-image' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr><td><?php esc_html_e( 'Enable comparison widget', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Turn the widget on or off for this product', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Display Location', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Show the slider in place of the product image, as a gallery image (with its own thumbnail), or below the product summary', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Before Image', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Image shown on the left side of the slider', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'After Image', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Image shown on the right side of the slider', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Before Label', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Text badge over the before image (e.g. "Before", "Original")', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'After Label', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Text badge over the after image (e.g. "After", "Restored")', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Orientation', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Horizontal (left/right drag) or Vertical (up/down drag)', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Handle Type', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Arrows or Text handle', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Labels Visibility', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'When before/after labels show: On Hover, Always, or Never', 'majestic-before-after-image' ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Image Size', 'majestic-before-after-image' ); ?></td><td><?php esc_html_e( 'Which registered image size to load (WooCommerce or WordPress sizes)', 'majestic-before-after-image' ); ?></td></tr>
				</tbody>
			</table>
		</div>
		<?php
	}
	/**
	 * Render the More Plugins tab content.
	 *
	 * Uses WordPress's native install/activate URLs — no custom AJAX needed.
	 *
	 * @since 3.0.0
	 */
	public function render_tab_more_plugins() {
		$this->admin_styles();

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = $this->mbai_get_wporg_plugins();

		// Map installed plugin files by slug for quick lookup.
		$installed       = get_plugins();
		$installed_slugs = array();
		foreach ( $installed as $file => $data ) {
			$slug                    = explode( '/', $file )[0];
			$installed_slugs[ $slug ] = $file;
		}
		?>
		<style>
			.mbai-mp-grid { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
			.mbai-mp-card { background: #fff; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; width: 260px; display: flex; flex-direction: column; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
			.mbai-mp-card-img { background: #f0f0f0; }
			.mbai-mp-card-img img { width: 100%; height: auto; display: block; }
			.mbai-mp-card-body { padding: 14px 16px; flex: 1; display: flex; flex-direction: column; }
			.mbai-mp-card-title { font-weight: 600; font-size: 14px; margin: 0 0 6px; }
			.mbai-mp-card-meta { font-size: 12px; color: #888; margin: 0 0 8px; display: flex; gap: 12px; align-items: center; }
			.mbai-mp-card-desc { font-size: 13px; color: #555; flex: 1; margin: 0 0 14px; line-height: 1.5; }
			.mbai-mp-card-footer { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
			.mbai-mp-card-footer .button { min-width: 90px; text-align: center; }
			.mbai-mp-stars { color: #f0ad00; font-size: 12px; letter-spacing: 1px; }
			.mbai-mp-installs { font-size: 11px; }
		</style>

		<div class="mbai-doc-section">
			<h2><?php esc_html_e( 'Our Other Free Plugins', 'majestic-before-after-image' ); ?></h2>
			<p><?php esc_html_e( 'All of the plugins below are free on WordPress.org. Click Install to add them to your site.', 'majestic-before-after-image' ); ?></p>
		</div>

		<?php if ( empty( $plugins ) ) : ?>
			<p><?php esc_html_e( 'Could not load plugins at this time. Please check back later.', 'majestic-before-after-image' ); ?></p>
		<?php else : ?>

		<div class="mbai-mp-grid">
		<?php foreach ( $plugins as $plugin ) :
			$slug    = isset( $plugin->slug )              ? $plugin->slug              : '';
			$name    = isset( $plugin->name )              ? $plugin->name              : '';
			$excerpt = isset( $plugin->short_description ) ? $plugin->short_description : '';

			// Banner image (772x250), fall back to icon.
			$image = '';
			if ( ! empty( $plugin->banners ) ) {
				$image = isset( $plugin->banners->low )  ? $plugin->banners->low  :
					   ( isset( $plugin->banners->high ) ? $plugin->banners->high : '' );
			}
			if ( empty( $image ) && ! empty( $plugin->icons ) ) {
				$image = isset( $plugin->icons->{'2x'} ) ? $plugin->icons->{'2x'} :
					   ( isset( $plugin->icons->{'1x'} ) ? $plugin->icons->{'1x'} :
					   ( isset( $plugin->icons->svg  )   ? $plugin->icons->svg    : '' ) );
			}

			$rating   = isset( $plugin->rating )          ? round( $plugin->rating / 20 ) : 0;
			$installs = isset( $plugin->active_installs ) ? $plugin->active_installs       : 0;
			$wporg_url = 'https://wordpress.org/plugins/' . $slug . '/';

			// Truncate excerpt to ~20 words.
			$words = explode( ' ', wp_strip_all_tags( $excerpt ) );
			if ( count( $words ) > 20 ) {
				$excerpt = implode( ' ', array_slice( $words, 0, 20 ) ) . '&hellip;';
			}

			// Format active installs.
			if ( $installs >= 1000000 ) {
				$installs_fmt = number_format( $installs / 1000000, 1 ) . 'M+';
			} elseif ( $installs >= 1000 ) {
				$installs_fmt = number_format( $installs / 1000, 0 ) . 'k+';
			} else {
				$installs_fmt = $installs . '+';
			}

			// Determine state: active / needs activation / not installed.
			$plugin_file  = isset( $installed_slugs[ $slug ] ) ? $installed_slugs[ $slug ] : '';
			$is_installed = ! empty( $plugin_file );
			$is_active    = $is_installed && is_plugin_active( $plugin_file );

			// Native WordPress install URL — goes through update.php exactly like "Add New".
			$install_url = wp_nonce_url(
				admin_url( 'update.php?action=install-plugin&plugin=' . urlencode( $slug ) ),
				'install-plugin_' . $slug
			);

			// Native WordPress activate URL.
			$activate_url = $is_installed && ! $is_active ? wp_nonce_url(
				admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $plugin_file ) ),
				'activate-plugin_' . $plugin_file
			) : '';
		?>
			<div class="mbai-mp-card">

				<?php if ( $image ) : ?>
				<div class="mbai-mp-card-img">
					<a href="<?php echo esc_url( $wporg_url ); ?>" target="_blank">
						<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $name ); ?>">
					</a>
				</div>
				<?php endif; ?>

				<div class="mbai-mp-card-body">
					<p class="mbai-mp-card-title">
						<a href="<?php echo esc_url( $wporg_url ); ?>" target="_blank"><?php echo esc_html( $name ); ?></a>
					</p>
					<div class="mbai-mp-card-meta">
						<span class="mbai-mp-stars"><?php echo esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) ); ?></span>
						<span class="mbai-mp-installs">
							<?php
							/* translators: %s: number of active installations, e.g. "10k+". */
							printf( esc_html__( '%s active installs', 'majestic-before-after-image' ), esc_html( $installs_fmt ) );
							?>
						</span>
					</div>
					<p class="mbai-mp-card-desc"><?php echo esc_html( $excerpt ); ?></p>
					<div class="mbai-mp-card-footer">
						<?php if ( $is_active ) : ?>
							<button class="button" disabled><?php esc_html_e( 'Active', 'majestic-before-after-image' ); ?></button>
						<?php elseif ( $is_installed ) : ?>
							<a href="<?php echo esc_url( $activate_url ); ?>" class="button button-primary"><?php esc_html_e( 'Activate', 'majestic-before-after-image' ); ?></a>
						<?php else : ?>
							<a href="<?php echo esc_url( $install_url ); ?>" class="button button-primary"><?php esc_html_e( 'Install Now', 'majestic-before-after-image' ); ?></a>
						<?php endif; ?>
						<a href="<?php echo esc_url( $wporg_url ); ?>" class="button button-secondary" target="_blank"><?php esc_html_e( 'Details', 'majestic-before-after-image' ); ?></a>
					</div>
				</div>

			</div>
		<?php endforeach; ?>
		</div>

		<?php endif; ?>
		<?php
	}

	/**
	 * Fetch the specific plugin list from the wordpress.org API.
	 *
	 * Results are cached as a transient for 12 hours.
	 *
	 * @since 3.0.0
	 *
	 * @return array Array of plugin objects, or empty array on failure.
	 */
	private function mbai_get_wporg_plugins() {
		$plugins = get_transient( '_mbai_wporg_plugins' );

		if ( false !== $plugins ) {
			return is_array( $plugins ) ? $plugins : array();
		}

		// The specific slugs to display — in the order we want to show them.
		$slugs = array(
			'contact-form-7-paypal-add-on',
			'easy-paypal-donation',
			'wp-ecommerce-paypal',
			'restore-paypal-standard-for-woocommerce',
			'time-clock',
		);

		$plugins = array();

		foreach ( $slugs as $slug ) {
			$url = add_query_arg(
				array(
					'action'                             => 'plugin_information',
					'request[slug]'                      => $slug,
					'request[fields][icons]'             => 1,
					'request[fields][rating]'            => 1,
					'request[fields][active_installs]'   => 1,
					'request[fields][short_description]' => 1,
					'request[fields][sections]'          => 0,
					'request[fields][banners]'           => 1,
				),
				'https://api.wordpress.org/plugins/info/1.2/'
			);

			$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( is_object( $data ) && ! empty( $data->slug ) ) {
				$plugins[] = $data;
			}
		}

		// Only cache if we got at least one result back.
		if ( ! empty( $plugins ) ) {
			set_transient( '_mbai_wporg_plugins', $plugins, 12 * HOUR_IN_SECONDS );
		}

		return $plugins;
	}
}

new MBAI_Admin_Page();
