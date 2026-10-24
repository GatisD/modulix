<?php
namespace Modulix\Modules\Social\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/social/widgets/facebook-comments.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Base\Base_Widget;
use Modulix\Modules\Social\Classes\Facebook_SDK_Manager;
use Modulix\Modules\Social\Module;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Facebook_Comments extends Base_Widget {

	public function get_name() {
		return 'facebook-comments';
	}

	public function get_title() {
		return esc_html__( 'Facebook Comments', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-facebook-comments';
	}

	public function get_keywords() {
		return [ 'facebook', 'comments', 'embed' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}

	/**
	 * Get style dependencies.
	 *
	 * Retrieve the list of style dependencies the widget requires.
	 *
	 * @since 3.24.0
	 * @access public
	 *
	 * @return array Widget style dependencies.
	 */
	public function get_style_depends(): array {
		return [ 'widget-social' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Comments Box', 'modulix' ),
			]
		);

		Facebook_SDK_Manager::add_app_id_control( $this );

		$this->add_control(
			'comments_number',
			[
				'label' => esc_html__( 'Comment Count', 'modulix' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 5,
				'max' => 100,
				'default' => '10',
				'description' => esc_html__( 'Minimum number of comments: 5', 'modulix' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'order_by',
			[
				'label' => esc_html__( 'Order By', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'social',
				'options' => [
					'social' => esc_html__( 'Social', 'modulix' ),
					'reverse_time' => esc_html__( 'Reverse Time', 'modulix' ),
					'time' => esc_html__( 'Time', 'modulix' ),
				],
			]
		);

		$this->add_control(
			'url_type',
			[
				'label' => esc_html__( 'Target URL', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					Module::URL_TYPE_CURRENT_PAGE => esc_html__( 'Current Page', 'modulix' ),
					Module::URL_TYPE_CUSTOM => esc_html__( 'Custom', 'modulix' ),
				],
				'default' => Module::URL_TYPE_CURRENT_PAGE,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'url_format',
			[
				'label' => esc_html__( 'URL Format', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					Module::URL_FORMAT_PLAIN => esc_html__( 'Plain Permalink', 'modulix' ),
					Module::URL_FORMAT_PRETTY => esc_html__( 'Pretty Permalink', 'modulix' ),
				],
				'default' => Module::URL_FORMAT_PLAIN,
				'condition' => [
					'url_type' => Module::URL_TYPE_CURRENT_PAGE,
				],
			]
		);

		$this->add_control(
			'url',
			[
				'label' => esc_html__( 'Link', 'modulix' ),
				'placeholder' => esc_html__( 'https://your-link.com', 'modulix' ),
				'label_block' => true,
				'condition' => [
					'url_type' => Module::URL_TYPE_CUSTOM,
				],
			]
		);

		$this->end_controls_section();
	}

	public function render() {
		$settings = $this->get_settings_for_display();

		if ( Module::URL_TYPE_CURRENT_PAGE === $settings['url_type'] ) {
			$permalink = Facebook_SDK_Manager::get_permalink( $settings );
		} else {
			if ( ! filter_var( $settings['url'], FILTER_VALIDATE_URL ) ) {
				echo $this->get_title() . ': ' . esc_html__( 'Please enter a valid URL', 'modulix' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				return;
			}

			$permalink = esc_url( $settings['url'] );
		}

		$attributes = [
			'class' => 'elementor-facebook-widget fb-comments',
			'data-href' => $permalink,
			'data-width' => '100%',
			'data-numposts' => $settings['comments_number'],
			'data-order-by' => $settings['order_by'],
			// The style prevent's the `widget.handleEmptyWidget` to set it as an empty widget
			'style' => 'min-height: 1px',
		];

		$this->add_render_attribute( 'embed_div', $attributes );
		?>
		<div <?php $this->print_render_attribute_string( 'embed_div' ); ?>></div>
		<?php
	}

	public function render_plain_content() {}

	public function get_group_name() {
		return 'social';
	}
}
