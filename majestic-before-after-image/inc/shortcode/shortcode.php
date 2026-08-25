<?php
/**
 * Shortcode: [mbai_before_after_image]
 *
 * Works without Elementor or WooCommerce.
 *
 * Usage examples:
 *
 *   [mbai_before_after_image before="https://…/before.jpg" after="https://…/after.jpg"]
 *
 *   [mbai_before_after_image before_id="42" after_id="57" orientation="vertical"
 *       before_label="Before" after_label="After" handle_type="text"
 *       handle_label="Drag" labels_status="always" handle_style="2"
 *       default_offset="0.5" overlay="yes" hover_move="no"]
 *
 * @package MBAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * MBAI_Shortcode class.
 *
 * @since 3.0.0
 */
class MBAI_Shortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'mbai_before_after_image', array( $this, 'render' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Enclosed content (unused).
	 * @return string Rendered HTML.
	 */
	public function render( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				// Images – pass either an attachment ID or a URL.
				'before_id'      => 0,
				'before'         => '',   // URL
				'after_id'       => 0,
				'after'          => '',   // URL

				// Labels.
				'before_label'   => __( 'Before', 'majestic-before-after-image' ),
				'after_label'    => __( 'After',  'majestic-before-after-image' ),
				'labels_status'  => 'hover',   // hover | always | never

				// Handle.
				'handle_type'    => 'arrows',  // arrows | text
				'handle_label'   => __( 'Drag', 'majestic-before-after-image' ),
				'handle_style'   => '1',       // 1–6
				'default_offset' => '0.5',

				// Behaviour.
				'orientation'    => 'horizontal', // horizontal | vertical
				'overlay'        => 'no',
				'hover_move'     => 'no',

				// Layout.
				'align'          => '',    // left | center | right | (empty = full width)

				// Slider colour (white = default, black).
				'color'          => 'white', // white | black

				// Image size (WordPress size slug when using attachment IDs).
				'image_size'     => 'large',
			),
			$atts,
			'mbai_before_after_image'
		);

		$before_id      = absint( $atts['before_id'] );
		$after_id       = absint( $atts['after_id'] );
		$before_url     = esc_url( $atts['before'] );
		$after_url      = esc_url( $atts['after'] );

		// Resolve URL from ID when no explicit URL supplied.
		if ( $before_id > 0 && empty( $before_url ) ) {
			$src = wp_get_attachment_image_src( $before_id, $atts['image_size'] );
			if ( $src ) { $before_url = $src[0]; }
		}
		if ( $after_id > 0 && empty( $after_url ) ) {
			$src = wp_get_attachment_image_src( $after_id, $atts['image_size'] );
			if ( $src ) { $after_url = $src[0]; }
		}

		// Bail if no images.
		if ( empty( $before_url ) || empty( $after_url ) ) {
			return '<!-- mbai_before_after_image: before/after images required -->';
		}

		$allowed_orientations  = array( 'horizontal', 'vertical' );
		$allowed_handle_types  = array( 'arrows', 'text' );
		$allowed_labels_status = array( 'hover', 'always', 'never' );
		$allowed_aligns        = array( 'left', 'center', 'right' );

		$orientation   = in_array( $atts['orientation'],   $allowed_orientations,  true ) ? $atts['orientation']   : 'horizontal';
		$handle_type   = in_array( $atts['handle_type'],   $allowed_handle_types,  true ) ? $atts['handle_type']   : 'arrows';
		$labels_status = in_array( $atts['labels_status'], $allowed_labels_status, true ) ? $atts['labels_status'] : 'hover';
		$align         = in_array( $atts['align'],         $allowed_aligns,        true ) ? $atts['align']         : '';
		$handle_style  = absint( $atts['handle_style'] );
		if ( $handle_style < 1 || $handle_style > 6 ) { $handle_style = 1; }

		$allowed_colors = array( 'white', 'black' );
		$color          = in_array( strtolower( $atts['color'] ), $allowed_colors, true ) ? strtolower( $atts['color'] ) : 'white';

		$data = array(
			'orientation'          => $orientation,
			'labels_status'        => $labels_status,
			'before_label'         => sanitize_text_field( $atts['before_label'] ),
			'after_label'          => sanitize_text_field( $atts['after_label'] ),
			'handle_type'          => $handle_type,
			'handle_label'         => sanitize_text_field( $atts['handle_label'] ),
			'handle_offset'        => (float) $atts['default_offset'],
			'overlay_status'       => ( 'no' !== strtolower( $atts['overlay'] ) ),
			'move_slider_on_hover' => ( 'yes' === strtolower( $atts['hover_move'] ) ),
		);

		$before_alt = sanitize_text_field( $atts['before_label'] );
		$after_alt  = sanitize_text_field( $atts['after_label'] );

		ob_start();

		// Alignment wrapper.
		if ( $align ) {
			echo '<div class="mbai-align-wrap mbai-align-' . esc_attr( $align ) . '">';
		}
		?>
		<div class="mbai-before-after-wrap handle-type-<?php echo esc_attr( $handle_type ); ?> handle-style-<?php echo esc_attr( $handle_style ); ?> mbai-color-<?php echo esc_attr( $color ); ?>" data-mbai="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
			<div class="mbai-before-after-container">
				<?php if ( $before_id > 0 ) : ?>
					<?php echo wp_get_attachment_image( $before_id, $atts['image_size'], '', array( 'class' => 'img-before', 'alt' => $before_alt ) ); ?>
				<?php else : ?>
					<img src="<?php echo esc_url( $before_url ); ?>" class="img-before" alt="<?php echo esc_attr( $before_alt ); ?>">
				<?php endif; ?>

				<?php if ( $after_id > 0 ) : ?>
					<?php echo wp_get_attachment_image( $after_id, $atts['image_size'], '', array( 'class' => 'img-after', 'alt' => $after_alt ) ); ?>
				<?php else : ?>
					<img src="<?php echo esc_url( $after_url ); ?>" class="img-after" alt="<?php echo esc_attr( $after_alt ); ?>">
				<?php endif; ?>
			</div><!-- .mbai-before-after-container -->
		</div><!-- .mbai-before-after-wrap -->
		<?php
		if ( $align ) {
			echo '</div>';
		}
		return ob_get_clean();
	}
}

new MBAI_Shortcode();
