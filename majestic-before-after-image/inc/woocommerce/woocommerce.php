<?php
/**
 * WooCommerce integration
 *
 * Adds a "Before After Image" meta box to WooCommerce products.
 * The comparison can be displayed either below the product summary or in
 * place of the product image / gallery, depending on the per-product
 * "Display Location" setting.
 *
 * @package MBAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * MBAI_WooCommerce class.
 *
 * @since 3.0.0
 */
class MBAI_WooCommerce {

	/**
	 * Meta key prefix.
	 */
	const META_PREFIX = '_mbai_woo_';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Meta box.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_product', array( $this, 'save_meta' ), 10, 2 );

		// Decide front-end placement once we know which product is being viewed.
		add_action( 'wp', array( $this, 'setup_display' ) );
	}

	/**
	 * Set up the front-end display based on the chosen location.
	 *
	 * - "summary"          : render below the product summary (default).
	 * - "gallery"          : replace the product image / gallery with the comparison.
	 * - "alongside_gallery": keep the gallery and add the comparison above it.
	 *
	 * @since 3.0.0
	 */
	public function setup_display() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		if ( '1' !== get_post_meta( $post_id, self::META_PREFIX . 'enabled', true ) ) {
			return;
		}

		$location = get_post_meta( $post_id, self::META_PREFIX . 'display_location', true );
		$location = $location ? $location : 'summary';

		if ( 'gallery' === $location ) {
			// Replace WooCommerce's default product images block with the comparison.
			remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
			add_action( 'woocommerce_before_single_product_summary', array( $this, 'render_comparison' ), 20 );
		} elseif ( 'alongside_gallery' === $location ) {
			// Inject the comparison as an extra slide inside the existing gallery.
			// This hook fires within the gallery <figure> wrapper, after the
			// product images, so FlexSlider treats it as a real slide and builds
			// a thumbnail nav entry for it.
			add_action( 'woocommerce_product_thumbnails', array( $this, 'render_gallery_slide' ), 30 );
		} else {
			// Default: render below the product summary.
			add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_comparison' ), 15 );
		}
	}

	/**
	 * Register the product meta box.
	 */
	public function add_meta_box() {
		add_meta_box(
			'mbai-woo-comparison',
			esc_html__( 'Before After Image', 'majestic-before-after-image' ),
			array( $this, 'render_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Get the list of available image sizes for the size dropdown.
	 *
	 * A curated list — WooCommerce's own sizes plus the core WordPress sizes.
	 * The auto-generated intermediate sizes (medium_large / 1536x1536 /
	 * 2048x2048) are intentionally excluded to keep the choice simple.
	 *
	 * @since 3.0.0
	 *
	 * @return array Map of size slug => human-readable label.
	 */
	public function get_image_size_options() {
		$sizes = array();

		// WooCommerce-specific sizes (only present when Woo is active).
		if ( function_exists( 'wc_get_image_size' ) ) {
			$sizes['woocommerce_single']    = esc_html__( 'WooCommerce Single (recommended)', 'majestic-before-after-image' );
			$sizes['woocommerce_thumbnail'] = esc_html__( 'WooCommerce Thumbnail', 'majestic-before-after-image' );
		}

		// Core WordPress sizes.
		$sizes['thumbnail'] = esc_html__( 'Thumbnail', 'majestic-before-after-image' );
		$sizes['medium']    = esc_html__( 'Medium', 'majestic-before-after-image' );
		$sizes['large']     = esc_html__( 'Large', 'majestic-before-after-image' );
		$sizes['full']      = esc_html__( 'Full', 'majestic-before-after-image' );

		return $sizes;
	}

	/**
	 * Default image size used when none is saved.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	private function default_image_size() {
		return function_exists( 'wc_get_image_size' ) ? 'woocommerce_single' : 'large';
	}

	/**
	 * Render meta box content.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'mbai_woo_meta_nonce', 'mbai_woo_nonce' );

		$enabled        = get_post_meta( $post->ID, self::META_PREFIX . 'enabled',         true );
		$before_id      = get_post_meta( $post->ID, self::META_PREFIX . 'before_image_id', true );
		$before_url     = get_post_meta( $post->ID, self::META_PREFIX . 'before_image_url', true );
		$after_id       = get_post_meta( $post->ID, self::META_PREFIX . 'after_image_id',  true );
		$after_url      = get_post_meta( $post->ID, self::META_PREFIX . 'after_image_url',  true );
		$before_label   = get_post_meta( $post->ID, self::META_PREFIX . 'before_label',    true );
		$after_label    = get_post_meta( $post->ID, self::META_PREFIX . 'after_label',     true );
		$orientation    = get_post_meta( $post->ID, self::META_PREFIX . 'orientation',     true );
		$handle_type    = get_post_meta( $post->ID, self::META_PREFIX . 'handle_type',     true );
		$labels_status  = get_post_meta( $post->ID, self::META_PREFIX . 'labels_status',   true );
		$display_loc    = get_post_meta( $post->ID, self::META_PREFIX . 'display_location', true );
		$image_size     = get_post_meta( $post->ID, self::META_PREFIX . 'image_size',      true );
		$color          = get_post_meta( $post->ID, self::META_PREFIX . 'color',           true );

		$before_label  = $before_label  ?: esc_html__( 'Before', 'majestic-before-after-image' );
		$after_label   = $after_label   ?: esc_html__( 'After',  'majestic-before-after-image' );
		$orientation   = $orientation   ?: 'horizontal';
		$handle_type   = $handle_type   ?: 'arrows';
		$labels_status = $labels_status ?: 'hover';
		$display_loc   = $display_loc   ?: 'summary';
		$image_size    = $image_size    ?: $this->default_image_size();
		$color         = $color         ?: 'white';

		// Thumbnail URLs for display.
		$before_thumb = $before_id ? wp_get_attachment_thumb_url( $before_id ) : $before_url;
		$after_thumb  = $after_id  ? wp_get_attachment_thumb_url( $after_id )  : $after_url;
		?>
		<style>
			.mbai-meta-row { margin: 10px 0; }
			.mbai-meta-row label { display: block; font-weight: 600; margin-bottom: 4px; }
			.mbai-meta-image-preview { max-width: 120px; max-height: 80px; margin-top: 6px; display: block; border: 1px solid #ddd; }
			.mbai-meta-image-wrap { display: inline-block; vertical-align: top; margin-right: 30px; }
		</style>

		<div class="mbai-meta-row">
			<label>
				<input type="checkbox" name="mbai_woo_enabled" value="1" <?php checked( $enabled, '1' ); ?>>
				<?php esc_html_e( 'Enable before / after image on product page', 'majestic-before-after-image' ); ?>
			</label>
		</div>

		<div class="mbai-meta-row">
			<label><?php esc_html_e( 'Display Location', 'majestic-before-after-image' ); ?></label>
			<select name="mbai_woo_display_location">
				<option value="gallery" <?php selected( $display_loc, 'gallery' ); ?>><?php esc_html_e( 'Replace product image / gallery', 'majestic-before-after-image' ); ?></option>
				<option value="alongside_gallery" <?php selected( $display_loc, 'alongside_gallery' ); ?>><?php esc_html_e( 'As a gallery image', 'majestic-before-after-image' ); ?></option>
				<option value="summary" <?php selected( $display_loc, 'summary' ); ?>><?php esc_html_e( 'Below product summary', 'majestic-before-after-image' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Choose where the comparison slider appears: in place of the product image, as a gallery image within the existing gallery (with its own thumbnail), or below the product summary.', 'majestic-before-after-image' ); ?></p>
		</div>

		<div class="mbai-meta-row">
			<div class="mbai-meta-image-wrap">
				<label><?php esc_html_e( 'Before Image', 'majestic-before-after-image' ); ?></label>
				<input type="hidden" name="mbai_woo_before_image_id"  id="mbai_woo_before_image_id"  value="<?php echo esc_attr( $before_id ); ?>">
				<input type="hidden" name="mbai_woo_before_image_url" id="mbai_woo_before_image_url" value="<?php echo esc_attr( $before_url ); ?>">
				<button type="button" class="button mbai-upload-btn" data-target="before"><?php esc_html_e( 'Select Image', 'majestic-before-after-image' ); ?></button>
				<?php if ( $before_thumb ) : ?>
					<img src="<?php echo esc_url( $before_thumb ); ?>" class="mbai-meta-image-preview" id="mbai_woo_before_preview">
				<?php else : ?>
					<img src="" class="mbai-meta-image-preview" id="mbai_woo_before_preview" style="display:none;">
				<?php endif; ?>
			</div>

			<div class="mbai-meta-image-wrap">
				<label><?php esc_html_e( 'After Image', 'majestic-before-after-image' ); ?></label>
				<input type="hidden" name="mbai_woo_after_image_id"  id="mbai_woo_after_image_id"  value="<?php echo esc_attr( $after_id ); ?>">
				<input type="hidden" name="mbai_woo_after_image_url" id="mbai_woo_after_image_url" value="<?php echo esc_attr( $after_url ); ?>">
				<button type="button" class="button mbai-upload-btn" data-target="after"><?php esc_html_e( 'Select Image', 'majestic-before-after-image' ); ?></button>
				<?php if ( $after_thumb ) : ?>
					<img src="<?php echo esc_url( $after_thumb ); ?>" class="mbai-meta-image-preview" id="mbai_woo_after_preview">
				<?php else : ?>
					<img src="" class="mbai-meta-image-preview" id="mbai_woo_after_preview" style="display:none;">
				<?php endif; ?>
			</div>
		</div>

		<div class="mbai-meta-row">
			<label><?php esc_html_e( 'Image Size', 'majestic-before-after-image' ); ?></label>
			<select name="mbai_woo_image_size">
				<?php foreach ( $this->get_image_size_options() as $size_slug => $size_label ) : ?>
					<option value="<?php echo esc_attr( $size_slug ); ?>" <?php selected( $image_size, $size_slug ); ?>><?php echo esc_html( $size_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Which registered image size to load for the before/after images.', 'majestic-before-after-image' ); ?></p>
		</div>

		<div class="mbai-meta-row">
			<label><?php esc_html_e( 'Before Label', 'majestic-before-after-image' ); ?></label>
			<input type="text" name="mbai_woo_before_label" value="<?php echo esc_attr( $before_label ); ?>" class="regular-text">
		</div>

		<div class="mbai-meta-row">
			<label><?php esc_html_e( 'After Label', 'majestic-before-after-image' ); ?></label>
			<input type="text" name="mbai_woo_after_label" value="<?php echo esc_attr( $after_label ); ?>" class="regular-text">
		</div>

		<div class="mbai-meta-row">
			<label><?php esc_html_e( 'Orientation', 'majestic-before-after-image' ); ?></label>
			<select name="mbai_woo_orientation">
				<option value="horizontal" <?php selected( $orientation, 'horizontal' ); ?>><?php esc_html_e( 'Horizontal', 'majestic-before-after-image' ); ?></option>
				<option value="vertical"   <?php selected( $orientation, 'vertical' ); ?>><?php esc_html_e( 'Vertical',   'majestic-before-after-image' ); ?></option>
			</select>
		</div>

		<div class="mbai-meta-row">
			<label><?php esc_html_e( 'Handle Type', 'majestic-before-after-image' ); ?></label>
			<select name="mbai_woo_handle_type">
				<option value="arrows" <?php selected( $handle_type, 'arrows' ); ?>><?php esc_html_e( 'Arrows', 'majestic-before-after-image' ); ?></option>
				<option value="text"   <?php selected( $handle_type, 'text' ); ?>><?php esc_html_e( 'Text',   'majestic-before-after-image' ); ?></option>
			</select>
		</div>

		<div class="mbai-meta-row">
			<label><?php esc_html_e( 'Slider Color', 'majestic-before-after-image' ); ?></label>
			<select name="mbai_woo_color">
				<option value="white" <?php selected( $color, 'white' ); ?>><?php esc_html_e( 'White', 'majestic-before-after-image' ); ?></option>
				<option value="black" <?php selected( $color, 'black' ); ?>><?php esc_html_e( 'Black', 'majestic-before-after-image' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Colour of the slider line, handle and arrows. Text handles flip colour automatically for contrast.', 'majestic-before-after-image' ); ?></p>
		</div>

		<div class="mbai-meta-row">
			<label><?php esc_html_e( 'Labels Visibility', 'majestic-before-after-image' ); ?></label>
			<select name="mbai_woo_labels_status">
				<option value="hover"  <?php selected( $labels_status, 'hover' ); ?>><?php esc_html_e( 'On Hover', 'majestic-before-after-image' ); ?></option>
				<option value="always" <?php selected( $labels_status, 'always' ); ?>><?php esc_html_e( 'Always',   'majestic-before-after-image' ); ?></option>
				<option value="never"  <?php selected( $labels_status, 'never' ); ?>><?php esc_html_e( 'Never',    'majestic-before-after-image' ); ?></option>
			</select>
		</div>

		<script>
		( function( $ ) {
			$( '.mbai-upload-btn' ).on( 'click', function( e ) {
				e.preventDefault();
				var target  = $( this ).data( 'target' );
				var idField = $( '#mbai_woo_' + target + '_image_id' );
				var urlField = $( '#mbai_woo_' + target + '_image_url' );
				var preview = $( '#mbai_woo_' + target + '_preview' );

				var frame = wp.media( {
					title:    '<?php echo esc_js( __( 'Select Image', 'majestic-before-after-image' ) ); ?>',
					button:   { text: '<?php echo esc_js( __( 'Use Image', 'majestic-before-after-image' ) ); ?>' },
					multiple: false,
				} );

				frame.on( 'select', function() {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					idField.val( attachment.id );
					urlField.val( attachment.url );
					preview.attr( 'src', attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url );
					preview.show();
				} );

				frame.open();
			} );
		} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Save the meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta( $post_id, $post ) {
		// Security checks.
		if ( ! isset( $_POST['mbai_woo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mbai_woo_nonce'] ) ), 'mbai_woo_meta_nonce' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'enabled'          => 'mbai_woo_enabled',
			'before_image_id'  => 'mbai_woo_before_image_id',
			'before_image_url' => 'mbai_woo_before_image_url',
			'after_image_id'   => 'mbai_woo_after_image_id',
			'after_image_url'  => 'mbai_woo_after_image_url',
			'before_label'     => 'mbai_woo_before_label',
			'after_label'      => 'mbai_woo_after_label',
			'orientation'      => 'mbai_woo_orientation',
			'handle_type'      => 'mbai_woo_handle_type',
			'labels_status'    => 'mbai_woo_labels_status',
			'display_location' => 'mbai_woo_display_location',
			'image_size'       => 'mbai_woo_image_size',
			'color'            => 'mbai_woo_color',
		);

		$allowed_orientations   = array( 'horizontal', 'vertical' );
		$allowed_handle_types   = array( 'arrows', 'text' );
		$allowed_labels_status  = array( 'hover', 'always', 'never' );
		$allowed_locations      = array( 'summary', 'gallery', 'alongside_gallery' );
		$allowed_image_sizes    = array_keys( $this->get_image_size_options() );
		$allowed_colors         = array( 'white', 'black' );

		foreach ( $fields as $meta_key => $post_key ) {
			if ( ! isset( $_POST[ $post_key ] ) ) {
				if ( 'enabled' === $meta_key ) {
					update_post_meta( $post_id, self::META_PREFIX . $meta_key, '0' );
				}
				continue;
			}

			$raw = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );

			switch ( $meta_key ) {
				case 'enabled':
					$value = ( '1' === $raw ) ? '1' : '0';
					break;
				case 'before_image_id':
				case 'after_image_id':
					$value = absint( $raw );
					break;
				case 'before_image_url':
				case 'after_image_url':
					$value = esc_url_raw( $raw );
					break;
				case 'orientation':
					$value = in_array( $raw, $allowed_orientations, true ) ? $raw : 'horizontal';
					break;
				case 'handle_type':
					$value = in_array( $raw, $allowed_handle_types, true ) ? $raw : 'arrows';
					break;
				case 'labels_status':
					$value = in_array( $raw, $allowed_labels_status, true ) ? $raw : 'hover';
					break;
				case 'display_location':
					$value = in_array( $raw, $allowed_locations, true ) ? $raw : 'summary';
					break;
				case 'image_size':
					$value = in_array( $raw, $allowed_image_sizes, true ) ? $raw : $this->default_image_size();
					break;
				case 'color':
					$value = in_array( $raw, $allowed_colors, true ) ? $raw : 'white';
					break;
				default:
					$value = sanitize_text_field( $raw );
					break;
			}

			update_post_meta( $post_id, self::META_PREFIX . $meta_key, $value );
		}
	}

	/**
	 * Render the comparison widget.
	 *
	 * Used both below the product summary and (in "gallery" mode) in place of
	/**
	 * Collect and validate this product's comparison settings.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id Product ID.
	 * @return array|false Settings array, or false if not usable.
	 */
	protected function get_settings( $post_id ) {
		if ( '1' !== get_post_meta( $post_id, self::META_PREFIX . 'enabled', true ) ) {
			return false;
		}

		$before_id  = absint( get_post_meta( $post_id, self::META_PREFIX . 'before_image_id', true ) );
		$before_url = get_post_meta( $post_id, self::META_PREFIX . 'before_image_url', true );
		$after_id   = absint( get_post_meta( $post_id, self::META_PREFIX . 'after_image_id', true ) );
		$after_url  = get_post_meta( $post_id, self::META_PREFIX . 'after_image_url', true );

		$has_before = ( $before_id > 0 || ! empty( $before_url ) );
		$has_after  = ( $after_id  > 0 || ! empty( $after_url ) );

		if ( ! $has_before || ! $has_after ) {
			return false;
		}

		return array(
			'before_id'     => $before_id,
			'before_url'    => $before_url,
			'after_id'      => $after_id,
			'after_url'     => $after_url,
			'before_label'  => get_post_meta( $post_id, self::META_PREFIX . 'before_label',    true ) ?: __( 'Before', 'majestic-before-after-image' ),
			'after_label'   => get_post_meta( $post_id, self::META_PREFIX . 'after_label',     true ) ?: __( 'After',  'majestic-before-after-image' ),
			'orientation'   => get_post_meta( $post_id, self::META_PREFIX . 'orientation',     true ) ?: 'horizontal',
			'handle_type'   => get_post_meta( $post_id, self::META_PREFIX . 'handle_type',     true ) ?: 'arrows',
			'labels_status' => get_post_meta( $post_id, self::META_PREFIX . 'labels_status',   true ) ?: 'hover',
			'location'      => get_post_meta( $post_id, self::META_PREFIX . 'display_location', true ) ?: 'summary',
			'image_size'    => get_post_meta( $post_id, self::META_PREFIX . 'image_size',      true ) ?: $this->default_image_size(),
			'color'         => get_post_meta( $post_id, self::META_PREFIX . 'color',           true ) ?: 'white',
		);
	}

	/**
	 * Output the inner slider markup (.mbai-before-after-wrap + images).
	 *
	 * @since 3.0.0
	 *
	 * @param array $s Settings from get_settings().
	 */
	protected function render_slider_markup( $s ) {
		$data = array(
			'orientation'          => $s['orientation'],
			'labels_status'        => $s['labels_status'],
			'before_label'         => $s['before_label'],
			'after_label'          => $s['after_label'],
			'handle_type'          => $s['handle_type'],
			'handle_label'         => esc_html__( 'Drag', 'majestic-before-after-image' ),
			'handle_offset'        => 0.5,
			'overlay_status'       => false,
			'move_slider_on_hover' => false,
		);
		?>
		<div class="mbai-before-after-wrap handle-type-<?php echo esc_attr( $s['handle_type'] ); ?> handle-style-1 mbai-color-<?php echo esc_attr( ! empty( $s['color'] ) ? $s['color'] : 'white' ); ?>" data-mbai="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
			<div class="mbai-before-after-container">
				<?php
				if ( $s['before_id'] > 0 ) {
					echo wp_get_attachment_image( $s['before_id'], $s['image_size'], '', array( 'class' => 'img-before', 'alt' => esc_attr( $s['before_label'] ) ) );
				} else {
					echo '<img src="' . esc_url( $s['before_url'] ) . '" class="img-before" alt="' . esc_attr( $s['before_label'] ) . '">';
				}

				if ( $s['after_id'] > 0 ) {
					echo wp_get_attachment_image( $s['after_id'], $s['image_size'], '', array( 'class' => 'img-after', 'alt' => esc_attr( $s['after_label'] ) ) );
				} else {
					echo '<img src="' . esc_url( $s['after_url'] ) . '" class="img-after" alt="' . esc_attr( $s['after_label'] ) . '">';
				}
				?>
			</div><!-- .mbai-before-after-container -->
		</div><!-- .mbai-before-after-wrap -->
		<?php
	}

	/**
	 * Render the comparison widget (summary and replace-gallery modes).
	 *
	 * Hooked dynamically in setup_display().
	 */
	public function render_comparison() {
		global $product;

		if ( ! $product ) {
			return;
		}

		$s = $this->get_settings( $product->get_id() );

		if ( ! $s ) {
			return;
		}

		$wrap_class = 'mbai-woo-wrap mbai-woo-location-' . esc_attr( $s['location'] );

		// In gallery mode, mirror WooCommerce's gallery wrapper classes so the
		// theme treats this as the product image column (correct float/width)
		// instead of letting the summary collapse on top of it.
		if ( 'gallery' === $s['location'] ) {
			$wrap_class .= ' woocommerce-product-gallery woocommerce-product-gallery--with-images images';
		}
		?>
		<div class="<?php echo esc_attr( $wrap_class ); ?>">
			<?php $this->render_slider_markup( $s ); ?>
		</div><!-- .mbai-woo-wrap -->
		<?php
	}

	/**
	 * Render the comparison as a slide inside the existing product gallery.
	 *
	 * Hooked to `woocommerce_product_thumbnails` so it appears as an extra
	 * gallery slide with its own thumbnail in the gallery navigation.
	 *
	 * @since 3.0.0
	 */
	public function render_gallery_slide() {
		global $product;

		if ( ! $product ) {
			return;
		}

		$s = $this->get_settings( $product->get_id() );

		if ( ! $s || 'alongside_gallery' !== $s['location'] ) {
			return;
		}

		// Thumbnail for the gallery nav: use the "before" image at gallery-thumb size.
		$thumb_size = function_exists( 'wc_get_image_size' ) ? 'woocommerce_gallery_thumbnail' : 'thumbnail';

		$thumb_url = '';
		if ( $s['before_id'] > 0 ) {
			$thumb_src = wp_get_attachment_image_src( $s['before_id'], $thumb_size );
			if ( $thumb_src ) {
				$thumb_url = $thumb_src[0];
			}
		} elseif ( ! empty( $s['before_url'] ) ) {
			$thumb_url = $s['before_url'];
		}

		// "After" thumbnail — overlaid on the right half so the nav thumbnail
		// shows a genuine before/after split rather than the same image twice.
		$after_thumb_url = '';
		if ( $s['after_id'] > 0 ) {
			$after_src = wp_get_attachment_image_src( $s['after_id'], $thumb_size );
			if ( $after_src ) {
				$after_thumb_url = $after_src[0];
			}
		} elseif ( ! empty( $s['after_url'] ) ) {
			$after_thumb_url = $s['after_url'];
		}
		?>
		<div class="mbai-woo-wrap mbai-woo-location-alongside_gallery woocommerce-product-gallery__image" data-thumb="<?php echo esc_url( $thumb_url ); ?>">
			<?php $this->render_slider_markup( $s ); ?>
		</div>
		<script>
		/* Decorate this comparison's gallery thumbnail so it visually reads as a
		   before/after slider: a single overlay div shows the "before" image on
		   the left and "after" on the right, with a divider line and handle.
		   Using one overlay div keeps hover behaviour uniform across the whole
		   thumbnail (the native split-image hover is covered). */
		( function () {
			var beforeThumb = <?php echo wp_json_encode( $thumb_url ); ?>;
			var afterThumb  = <?php echo wp_json_encode( $after_thumb_url ); ?>;
			var orientation = <?php echo wp_json_encode( $s['orientation'] ); ?>;
			var sliderColor = <?php echo wp_json_encode( ! empty( $s['color'] ) ? $s['color'] : 'white' ); ?>;

			function decorate() {
				var gallery = document.querySelector( '.woocommerce-product-gallery' );
				if ( ! gallery ) { return false; }

				var slides = gallery.querySelectorAll( '.woocommerce-product-gallery__image' );
				var index  = -1;
				for ( var i = 0; i < slides.length; i++ ) {
					if ( slides[ i ].classList.contains( 'mbai-woo-location-alongside_gallery' ) ) {
						index = i;
						break;
					}
				}
				if ( index < 0 ) { return false; }

				var thumbs = gallery.querySelectorAll( '.flex-control-thumbs li' );
				if ( ! thumbs.length || ! thumbs[ index ] ) { return false; }

				var li = thumbs[ index ];
				li.classList.add( 'mbai-gallery-thumb' );

				// Build the overlay only once.
				if ( ! li.querySelector( '.mbai-thumb-overlay' ) ) {
					var overlay = document.createElement( 'div' );
					overlay.className = 'mbai-thumb-overlay mbai-thumb-overlay--' + ( orientation === 'vertical' ? 'vertical' : 'horizontal' );
					if ( sliderColor === 'black' ) {
						overlay.classList.add( 'mbai-thumb-overlay--black' );
					}
					if ( beforeThumb ) {
						overlay.style.setProperty( '--mbai-before-thumb', 'url("' + beforeThumb + '")' );
					}
					if ( afterThumb ) {
						overlay.style.setProperty( '--mbai-after-thumb', 'url("' + afterThumb + '")' );
						overlay.classList.add( 'mbai-thumb-overlay--split' );
					}

					// Arrow glyphs inside the centre handle circle.
					var arrows = document.createElement( 'div' );
					arrows.className = 'mbai-thumb-handle-arrows';
					if ( orientation === 'vertical' ) {
						arrows.classList.add( 'mbai-thumb-handle-arrows--vertical' );
						arrows.innerHTML = '<span class="mbai-thumb-arrow-up"></span><span class="mbai-thumb-arrow-down"></span>';
					} else {
						arrows.innerHTML = '<span class="mbai-thumb-arrow-left"></span><span class="mbai-thumb-arrow-right"></span>';
					}
					overlay.appendChild( arrows );

					li.appendChild( overlay );
				}

				// Hide WooCommerce's zoom (PhotoSwipe) trigger while our comparison
				// slide is active — there's no single image to zoom, and it can
				// break PhotoSwipe. Show it again on the real product images.
				setupZoomToggle( gallery, slides, index );

				return true;
			}

			/* Toggle a class on the gallery whenever the comparison slide is the
			   active one, so CSS can hide the zoom trigger for it. */
			function setupZoomToggle( gallery, slides, index ) {
				if ( gallery.getAttribute( 'data-mbai-zoom-bound' ) ) { return; }
				gallery.setAttribute( 'data-mbai-zoom-bound', '1' );

				var ourSlide = slides[ index ];

				function sync() {
					// FlexSlider marks the active slide with .flex-active-slide.
					var isActive = ourSlide.classList.contains( 'flex-active-slide' );
					gallery.classList.toggle( 'mbai-hide-zoom', isActive );
				}

				// Observe class changes on the slides to detect slide switches.
				if ( typeof MutationObserver !== 'undefined' ) {
					var observer = new MutationObserver( sync );
					for ( var i = 0; i < slides.length; i++ ) {
						observer.observe( slides[ i ], { attributes: true, attributeFilter: [ 'class' ] } );
					}
				}

				// Also sync on thumbnail clicks (covers themes/animations).
				var thumbItems = gallery.querySelectorAll( '.flex-control-thumbs li' );
				thumbItems.forEach( function ( t ) {
					t.addEventListener( 'click', function () { setTimeout( sync, 50 ); } );
				} );

				sync();
			}

			// FlexSlider builds the thumbnail nav asynchronously; retry briefly.
			var tries = 0;
			var timer = setInterval( function () {
				tries++;
				if ( decorate() || tries > 40 ) { clearInterval( timer ); }
			}, 150 );
			window.addEventListener( 'load', decorate );
		}() );
		</script>
		<?php
	}
}

new MBAI_WooCommerce();
