<?php
namespace Modulix\Modules\Social\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/social/widgets/facebook-button.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Base\Base_Widget;
use Modulix\Modules\Social\Classes\Facebook_SDK_Manager;
use Modulix\Modules\Social\Module;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Facebook_Button extends Base_Widget {

	public function get_name() {
		return 'facebook-button';
	}

	public function get_title() {
		return esc_html__( 'Facebook Button', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-facebook-like-box';
	}

	public function get_keywords() {
		return [ 'facebook', 'social', 'embed', 'button', 'like', 'share', 'recommend', 'follow' ];
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
				'label' => esc_html__( 'Button', 'modulix' ),
			]
		);

		Facebook_SDK_Manager::add_app_id_control( $this );

		$this->add_control(
			'type',
			[
				'label' => esc_html__( 'Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'like',
				'options' => [
					'like' => esc_html__( 'Like', 'modulix' ),
					'recommend' => esc_html__( 'Recommend', 'modulix' ),
				],
			]
		);

		$this->add_control(
			'layout',
			[
				'label' => esc_html__( 'Layout', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'standard',
				'options' => [
					'standard' => esc_html__( 'Standard', 'modulix' ),
					'button' => esc_html__( 'Button', 'modulix' ),
					'button_count' => esc_html__( 'Button Count', 'modulix' ),
					'box_count' => esc_html__( 'Box Count', 'modulix' ),
				],
			]
		);

		$this->add_control(
			'size',
			[
				'label' => esc_html__( 'Size', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'small',
				'options' => [
					'small' => esc_html__( 'Small', 'modulix' ),
					'large' => esc_html__( 'Large', 'modulix' ),
				],
			]
		);

		$this->add_control(
			'color_scheme',
			[
				'label' => esc_html__( 'Color Scheme', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'light',
				'options' => [
					'light' => esc_html__( 'Light', 'modulix' ),
					'dark' => esc_html__( 'Dark', 'modulix' ),
				],
			]
		);

		$this->add_control(
			'show_share',
			[
				'label' => esc_html__( 'Share Button', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'condition' => [
					'type!' => 'follow',
				],
			]
		);

		$this->add_control(
			'show_faces',
			[
				'label' => esc_html__( 'Faces', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
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
				'condition' => [
					'type' => [ 'like', 'recommend' ],
				],
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
					'type' => [ 'like', 'recommend' ],
					'url_type' => Module::URL_TYPE_CUSTOM,
				],
			]
		);

		$this->end_controls_section();
	}

	public function render() {
		$settings = $this->get_settings();

		// Validate URL
		switch ( $settings['type'] ) {
			case 'like':
			case 'recommend':
				if ( Module::URL_TYPE_CUSTOM === $settings['url_type'] && ! filter_var( $settings['url'], FILTER_VALIDATE_URL ) ) {
					if ( Plugin::elementor()->editor->is_edit_mode() ) {
						echo $this->get_title() . ': ' . esc_html__( 'Please enter a valid URL', 'modulix' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}

					return;
				}
				break;
		}

		$attributes = [
			'data-layout' => $settings['layout'],
			'data-colorscheme' => $settings['color_scheme'],
			'data-size' => $settings['size'],
			'data-show-faces' => $settings['show_faces'] ? 'true' : 'false',
		];

		switch ( $settings['type'] ) {
			case 'like':
			case 'recommend':
				if ( Module::URL_TYPE_CURRENT_PAGE === $settings['url_type'] ) {
					$permalink = Facebook_SDK_Manager::get_permalink( $settings );
				} else {
					$permalink = esc_url( $settings['url'] );
				}

				$attributes['class'] = 'elementor-facebook-widget fb-like';
				$attributes['data-href'] = $permalink;
				$attributes['data-share'] = $settings['show_share'] ? 'true' : 'false';
				$attributes['data-action'] = $settings['type'];
				break;
		}

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
