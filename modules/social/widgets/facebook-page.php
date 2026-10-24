<?php
namespace Modulix\Modules\Social\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/social/widgets/facebook-page.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Base\Base_Widget;
use Modulix\Modules\Social\Classes\Facebook_SDK_Manager;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Facebook_Page extends Base_Widget {

	public function get_name() {
		return 'facebook-page';
	}

	public function get_title() {
		return esc_html__( 'Facebook Page', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-fb-feed';
	}

	public function get_keywords() {
		return [ 'facebook', 'social', 'embed', 'page' ];
	}

	protected function is_dynamic_content(): bool {
		return false;
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
				'label' => esc_html__( 'Page', 'modulix' ),
			]
		);

		Facebook_SDK_Manager::add_app_id_control( $this );

		$this->add_control(
			'widget_exclusively_web',
			[
				'type' => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'content' => sprintf(
					esc_html__( 'Facebook page embedding is exclusively available for the web, mobile devices are not supported. %1$sLearn more%2$s', 'modulix' ),
					sprintf( '<a href="%s" target="_blank">', Facebook_SDK_Manager::FACEBOOK_PLUGINS_FAQ_URL ),
					'</a>'
				),
			]
		);

		$this->add_control(
			'url',
			[
				'label' => esc_html__( 'Link', 'modulix' ),
				'placeholder' => 'https://www.facebook.com/your-page/',
				'default' => 'https://www.facebook.com/elemntor/',
				'label_block' => true,
				'description' => esc_html__( 'Paste the URL of the Facebook page.', 'modulix' ),
			]
		);

		$this->add_control(
			'tabs',
			[
				'label' => esc_html__( 'Layout', 'modulix' ),
				'type' => Controls_Manager::SELECT2,
				'multiple' => true,
				'label_block' => true,
				'default' => [
					'timeline',
				],
				'options' => [
					'timeline' => esc_html__( 'Timeline', 'modulix' ),
					'events' => esc_html__( 'Events', 'modulix' ),
					'messages' => esc_html__( 'Messages', 'modulix' ),
				],
			]
		);

		$this->add_control(
			'small_header',
			[
				'label' => esc_html__( 'Small Header', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
			]
		);

		$this->add_control(
			'show_cover',
			[
				'label' => esc_html__( 'Cover Photo', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_facepile',
			[
				'label' => esc_html__( 'Profile Photos', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_cta',
			[
				'label' => esc_html__( 'Custom CTA Button', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'height',
			[
				'label' => esc_html__( 'Height', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default' => [
					'size' => 500,
				],
				'range' => [
					'px' => [
						'min' => 50,
						'max' => 1000,
					],
					'em' => [
						'min' => 5,
						'max' => 100,
					],
					'rem' => [
						'min' => 5,
						'max' => 100,
					],
				],
			]
		);

		$this->end_controls_section();
	}

	public function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['url'] ) ) {
			echo $this->get_title() . ': ' . esc_html__( 'Please enter a valid URL', 'modulix' ); // XSS ok.

			return;
		}

		$height = $settings['height']['size'] . $settings['height']['unit'];

		$attributes = [
			'class' => 'elementor-facebook-widget fb-page',
			'data-href' => $settings['url'],
			'data-tabs' => implode( ',', $settings['tabs'] ),
			'data-height' => $height,
			'data-width' => '500px', // Try the max possible width
			'data-small-header' => $settings['small_header'] ? 'true' : 'false',
			'data-hide-cover' => $settings['show_cover'] ? 'false' : 'true', // if `show` - don't hide.
			'data-show-facepile' => $settings['show_facepile'] ? 'true' : 'false',
			'data-hide-cta' => $settings['show_cta'] ? 'false' : 'true', // if `show` - don't hide.
			// The style prevent's the `widget.handleEmptyWidget` to set it as an empty widget.
			'style' => 'min-height: 1px;height:' . $height,
		];

		$this->add_render_attribute( 'embed_div', $attributes );

		echo '<div ' . $this->get_render_attribute_string( 'embed_div' ) . '></div>'; // XSS ok.
	}

	public function render_plain_content() {}

	public function get_group_name() {
		return 'social';
	}
}
