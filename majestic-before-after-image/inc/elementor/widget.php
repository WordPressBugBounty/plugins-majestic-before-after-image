<?php
/**
 * Widget
 *
 * @package MBAI
 */

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;

/**
 * MBAI Elementor widget class.
 *
 * @since 1.0.0
 */
class MBAI_Elementor_Widget extends Widget_Base {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Widget data.
	 * @param array $args Widget arguments.
	 */
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );

		// Core style & script (registered in MBAI::register_assets()).
		// Re-register here so Elementor can track them independently.
		wp_register_style( 'mbai-style', MBAI_URL . '/assets/css/mbai.css', array(), MBAI_VERSION );
		wp_register_script( 'mbai-script', MBAI_URL . '/assets/js/mbai.js', array(), MBAI_VERSION, true );

		// Elementor-specific handler (requires elementor-frontend).
		wp_register_script(
			'mbai-elementor-widget',
			MBAI_URL . '/assets/js/mbai-elementor.js',
			array( 'elementor-frontend', 'mbai-script' ),
			MBAI_VERSION,
			true
		);
	}

	/**
	 * Get widget name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'mbai-before-after-image';
	}

	/**
	 * Get widget title.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Before After Image', 'majestic-before-after-image' );
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'mbai-elementor-widget' );
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'mbai-style' );
	}

	/**
	 * Get widget icon.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-h-align-stretch';
	}

	/**
	 * Get widget categories.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'mbai-widgets' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'image', 'before', 'after', 'comparison', 'woocommerce' );
	}

	/**
	 * Register widget controls.
	 *
	 * @since 1.0.0
	 */
	protected function register_controls() {
		$this->content_general_options();
		$this->content_handle_options();

		$this->style_labels_options();
		$this->style_handle_options();
		$this->style_handle_text_options();
	}

	/**
	 * Content General Options.
	 */
	private function content_general_options() {
		$this->start_controls_section(
			'section_general',
			array(
				'label' => esc_html__( 'General', 'majestic-before-after-image' ),
			)
		);

		$this->add_control(
			'before_image',
			array(
				'label'   => esc_html__( 'Before Image', 'majestic-before-after-image' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array(
					'url' => Utils::get_placeholder_image_src(),
				),
			)
		);

		$this->add_control(
			'after_image',
			array(
				'label'   => esc_html__( 'After Image', 'majestic-before-after-image' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array(
					'url' => Utils::get_placeholder_image_src(),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'         => 'post_thumbnail',
				'exclude'      => array( 'custom' ),
				'default'      => 'full',
				'prefix_class' => 'post-thumbnail-size-',
			)
		);

		$this->add_control(
			'enable_overlay',
			array(
				'label'     => esc_html__( 'Enable Overlay', 'majestic-before-after-image' ),
				'type'      => Controls_Manager::SWITCHER,
				'label_on'  => esc_html__( 'Yes', 'majestic-before-after-image' ),
				'label_off' => esc_html__( 'No', 'majestic-before-after-image' ),
				'default'   => '',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'overlay_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Overlay Color', 'majestic-before-after-image' ),
				'default'   => 'rgba(0, 0, 0, 0.5)',
				'condition' => array( 'enable_overlay' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .mbai-overlay:hover' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'enable_labels',
			array(
				'label'     => esc_html__( 'Enable Labels', 'majestic-before-after-image' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'hover',
				'options'   => array(
					'hover'  => esc_html__( 'On Hover', 'majestic-before-after-image' ),
					'always' => esc_html__( 'Always',   'majestic-before-after-image' ),
					'never'  => esc_html__( 'Never',    'majestic-before-after-image' ),
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'before_label',
			array(
				'label'     => esc_html__( 'Before Text', 'majestic-before-after-image' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Before', 'majestic-before-after-image' ),
				'condition' => array( 'enable_labels!' => 'never' ),
			)
		);

		$this->add_control(
			'after_label',
			array(
				'label'     => esc_html__( 'After Text', 'majestic-before-after-image' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'After', 'majestic-before-after-image' ),
				'condition' => array( 'enable_labels!' => 'never' ),
			)
		);

		$this->add_control(
			'slider_orientation',
			array(
				'label'     => esc_html__( 'Orientation', 'majestic-before-after-image' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'horizontal' => array(
						'title' => esc_html__( 'Horizontal', 'majestic-before-after-image' ),
						'icon'  => 'eicon-h-align-stretch',
					),
					'vertical'   => array(
						'title' => esc_html__( 'Vertical', 'majestic-before-after-image' ),
						'icon'  => 'eicon-v-align-stretch',
					),
				),
				'default'   => 'horizontal',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'slider_color',
			array(
				'label'     => esc_html__( 'Slider Color', 'majestic-before-after-image' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'white',
				'options'   => array(
					'white' => esc_html__( 'White', 'majestic-before-after-image' ),
					'black' => esc_html__( 'Black', 'majestic-before-after-image' ),
				),
				'description' => esc_html__( 'Colour of the slider line, handle and arrows. For a text handle the text flips automatically for contrast.', 'majestic-before-after-image' ),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content Handle Options.
	 */
	private function content_handle_options() {
		$this->start_controls_section(
			'section_handle',
			array(
				'label' => esc_html__( 'Handle', 'majestic-before-after-image' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'handle_type',
			array(
				'label'   => esc_html__( 'Handle Type', 'majestic-before-after-image' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'arrows',
				'options' => array(
					'arrows' => esc_html__( 'Arrows', 'majestic-before-after-image' ),
					'text'   => esc_html__( 'Text',   'majestic-before-after-image' ),
				),
			)
		);

		$this->add_control(
			'handle_label',
			array(
				'label'     => esc_html__( 'Handle Text', 'majestic-before-after-image' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'DRAG', 'majestic-before-after-image' ),
				'condition' => array( 'handle_type' => 'text' ),
			)
		);

		$this->add_control(
			'handle_text_style_size',
			array(
				'type'      => Controls_Manager::SLIDER,
				'label'     => esc_html__( 'Circle Size', 'majestic-before-after-image' ),
				'default'   => array( 'size' => 50 ),
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
				'selectors' => array(
					'{{WRAPPER}} .handle-type-text .mbai-handle' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; margin-left: calc(-{{SIZE}}{{UNIT}} / 2 - 3px); margin-top: calc(-{{SIZE}}{{UNIT}} / 2 - 3px)',
					'{{WRAPPER}} .handle-type-text .mbai-handle-text' => 'height: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .handle-type-text .mbai-horizontal .mbai-handle::before' => 'margin-bottom: calc({{SIZE}}{{UNIT}} / 2 + 3px);',
					'{{WRAPPER}} .handle-type-text .mbai-horizontal .mbai-handle::after'  => 'margin-top: calc({{SIZE}}{{UNIT}} / 2 + 3px);',
					'{{WRAPPER}} .handle-type-text .mbai-vertical .mbai-handle::before'   => 'margin-left: calc({{SIZE}}{{UNIT}} / 2 + 3px);',
					'{{WRAPPER}} .handle-type-text .mbai-vertical .mbai-handle::after'    => 'margin-right: calc({{SIZE}}{{UNIT}} / 2 + 3px);',
				),
				'condition' => array( 'handle_type' => 'text' ),
			)
		);

		$this->add_control(
			'handle_style',
			array(
				'label'     => esc_html__( 'Handle Style', 'majestic-before-after-image' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '1',
				'options'   => array(
					'1' => esc_html__( 'Style 1', 'majestic-before-after-image' ),
					'2' => esc_html__( 'Style 2', 'majestic-before-after-image' ),
					'3' => esc_html__( 'Style 3', 'majestic-before-after-image' ),
					'4' => esc_html__( 'Style 4', 'majestic-before-after-image' ),
					'5' => esc_html__( 'Style 5', 'majestic-before-after-image' ),
					'6' => esc_html__( 'Style 6', 'majestic-before-after-image' ),
				),
				'condition' => array( 'handle_type' => 'arrows' ),
			)
		);

		$this->add_control(
			'handle_offset',
			array(
				'label'     => esc_html__( 'Default Offset', 'majestic-before-after-image' ),
				'type'      => Controls_Manager::SLIDER,
				'separator' => 'before',
				'default'   => array( 'size' => 0.5 ),
				'range'     => array( 'px' => array( 'max' => 1, 'min' => 0.1, 'step' => 0.1 ) ),
			)
		);

		$this->add_control(
			'move_slider_on_hover',
			array(
				'label'       => esc_html__( 'Enable On Mouse Hover', 'majestic-before-after-image' ),
				'description' => esc_html__( 'If enabled, the handle will move on mouse hover instead of dragging.', 'majestic-before-after-image' ),
				'type'        => Controls_Manager::SWITCHER,
				'label_on'    => esc_html__( 'Yes', 'majestic-before-after-image' ),
				'label_off'   => esc_html__( 'No',  'majestic-before-after-image' ),
				'default'     => 'no',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style > Labels.
	 */
	private function style_labels_options() {
		$this->start_controls_section(
			'section_labels_style',
			array(
				'label'     => esc_html__( 'Labels', 'majestic-before-after-image' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'enable_labels!' => 'never' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'labels_style_typography',
				'global'   => array( 'default' => Global_Typography::TYPOGRAPHY_ACCENT ),
				'selector' => '{{WRAPPER}} .mbai-before-label::before, {{WRAPPER}} .mbai-after-label::before',
			)
		);

		$this->add_control(
			'labels_style_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Color', 'majestic-before-after-image' ),
				'separator' => 'before',
				'default'   => '#fff',
				'selectors' => array(
					'{{WRAPPER}} .mbai-before-label::before' => 'color: {{VALUE}};',
					'{{WRAPPER}} .mbai-after-label::before'  => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'labels_style_background_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Background Color', 'majestic-before-after-image' ),
				'selectors' => array(
					'{{WRAPPER}} .mbai-before-label::before' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .mbai-after-label::before'  => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'labels_style_border_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Border Color', 'majestic-before-after-image' ),
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .mbai-before-label::before' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .mbai-after-label::before'  => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'labels_style_border_width',
			array(
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => esc_html__( 'Border Width', 'majestic-before-after-image' ),
				'size_units' => array( 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .mbai-before-label::before' => 'border-style: solid; border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
					'{{WRAPPER}} .mbai-after-label::before'  => 'border-style: solid; border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				),
			)
		);

		$this->add_control(
			'labels_style_border_radius',
			array(
				'type'      => Controls_Manager::SLIDER,
				'label'     => esc_html__( 'Border Radius', 'majestic-before-after-image' ),
				'default'   => array( 'size' => 0 ),
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors' => array(
					'{{WRAPPER}} .mbai-before-label::before' => 'border-radius: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .mbai-after-label::before'  => 'border-radius: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->add_responsive_control(
			'labels_style_button_padding',
			array(
				'label'      => esc_html__( 'Padding', 'majestic-before-after-image' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => array( 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .mbai-before-label::before' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
					'{{WRAPPER}} .mbai-after-label::before'  => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style > Handle Text.
	 *
	 * @since 1.0.0
	 */
	private function style_handle_text_options() {
		$this->start_controls_section(
			'section_handle_text_style',
			array(
				'label'     => esc_html__( 'Handle Text', 'majestic-before-after-image' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'handle_type' => 'text' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'handle_text_style_typography',
				'global'   => array( 'default' => Global_Typography::TYPOGRAPHY_ACCENT ),
				'selector' => '{{WRAPPER}} .mbai-handle-text',
			)
		);

		$this->add_control(
			'handle_text_style_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Color', 'majestic-before-after-image' ),
				'separator' => 'before',
				'default'   => '#000',
				'selectors' => array(
					'{{WRAPPER}} .mbai-handle-text' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'handle_text_style_background_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Background Color', 'majestic-before-after-image' ),
				'selectors' => array(
					'{{WRAPPER}} .mbai-handle-text' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style > Handle.
	 */
	private function style_handle_options() {
		$this->start_controls_section(
			'section_handle_style',
			array(
				'label' => esc_html__( 'Handle', 'majestic-before-after-image' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'handle_style_background_color',
			array(
				'type'      => Controls_Manager::COLOR,
				'label'     => esc_html__( 'Handle Color', 'majestic-before-after-image' ),
				'selectors' => array(
					'{{WRAPPER}} .mbai-handle'                              => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .mbai-horizontal .mbai-handle::before'     => 'background: {{VALUE}};',
					'{{WRAPPER}} .mbai-horizontal .mbai-handle::after'      => 'background: {{VALUE}};',
					'{{WRAPPER}} .mbai-vertical .mbai-handle::before'       => 'background: {{VALUE}};',
					'{{WRAPPER}} .mbai-vertical .mbai-handle::after'        => 'background: {{VALUE}};',
					'{{WRAPPER}} .mbai-handle .mbai-left-arrow'             => 'border-right-color: {{VALUE}};',
					'{{WRAPPER}} .mbai-handle .mbai-right-arrow'            => 'border-left-color: {{VALUE}};',
					'{{WRAPPER}} .mbai-handle .mbai-down-arrow'             => 'border-top-color: {{VALUE}};',
					'{{WRAPPER}} .mbai-handle .mbai-up-arrow'               => 'border-bottom-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance Widget instance.
	 */
	protected function render( $instance = array() ) {
		$settings = $this->get_settings_for_display();

		$data = array(
			'orientation'          => esc_attr( $settings['slider_orientation'] ),
			'labels_status'        => esc_attr( $settings['enable_labels'] ),
			'before_label'         => esc_attr( $settings['before_label'] ),
			'after_label'          => esc_attr( $settings['after_label'] ),
			'handle_type'          => esc_attr( $settings['handle_type'] ),
			'handle_label'         => esc_attr( $settings['handle_label'] ),
			'handle_offset'        => floatval( $settings['handle_offset']['size'] ),
			'overlay_status'       => ( 'yes' === $settings['enable_overlay'] ),
			'move_slider_on_hover' => ( 'yes' === $settings['move_slider_on_hover'] ),
		);
		?>
		<div class="mbai-before-after-wrap handle-type-<?php echo esc_attr( $settings['handle_type'] ); ?> handle-style-<?php echo absint( $settings['handle_style'] ); ?> mbai-color-<?php echo esc_attr( ! empty( $settings['slider_color'] ) ? $settings['slider_color'] : 'white' ); ?>" data-mbai='<?php echo esc_attr( wp_json_encode( $data ) ); ?>'>

			<div class="mbai-before-after-container">
				<?php $this->render_images( $settings ); ?>
			</div><!-- .mbai-before-after-container -->

		</div><!-- .mbai-before-after-wrap -->
		<?php
	}

	/**
	 * Render before/after images.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Widget settings.
	 */
	protected function render_images( array $settings ) {
		$before_image        = $settings['before_image'];
		$after_image         = $settings['after_image'];
		$post_thumbnail_size = $settings['post_thumbnail_size'];

		if ( absint( $before_image['id'] ) > 0 ) {
			echo wp_get_attachment_image( $before_image['id'], $post_thumbnail_size, '', array( 'class' => 'img-before' ) );
		} elseif ( ! empty( $before_image['url'] ) ) {
			echo '<img src="' . esc_url( $before_image['url'] ) . '" class="img-before" alt="' . esc_attr__( 'Before Image', 'majestic-before-after-image' ) . '">';
		}

		if ( absint( $after_image['id'] ) > 0 ) {
			echo wp_get_attachment_image( $after_image['id'], $post_thumbnail_size, '', array( 'class' => 'img-after' ) );
		} elseif ( ! empty( $after_image['url'] ) ) {
			echo '<img src="' . esc_url( $after_image['url'] ) . '" class="img-after" alt="' . esc_attr__( 'After Image', 'majestic-before-after-image' ) . '">';
		}
	}
}
