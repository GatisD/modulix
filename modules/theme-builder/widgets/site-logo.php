<?php
namespace Modulix\Modules\ThemeBuilder\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/widgets/site-logo.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): licence trait removed.

use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Utils;
use Elementor\Widget_Image;
use Modulix\Modules\ThemeBuilder\Classes\Control_Media_Preview;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Site_Logo extends Widget_Image {

	public function get_name() {
		// `theme` prefix is to avoid conflicts with a dynamic-tag with same name.
		return 'theme-site-logo';
	}

	public function get_title() {
		return esc_html__( 'Site Logo', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-site-logo';
	}

	public function get_categories() {
		return [ 'theme-elements' ];
	}

	public function get_keywords() {
		return [ 'site', 'logo', 'branding' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}

	protected function register_controls() {
		parent::register_controls();

		$this->update_control(
			'section_image',
			[
				'label' => esc_html__( 'Site Logo', 'modulix' ),
			]
		);

		$this->update_control(
			'image',
			[
				'label' => esc_html__( 'Site Logo', 'modulix' ),
				'type' => Control_Media_Preview::CONTROL_TYPE,
				'src' => $this->get_site_logo(),
				'dynamic' => [
					'default' => Plugin::elementor()->dynamic_tags->tag_data_to_tag_text( null, 'site-logo' ),
				],
			],
			[
				'recursive' => true,
			]
		);

		$this->update_control(
			'image_size',
			[
				'separator' => 'before',
				'default' => 'full',
			]
		);

		$this->update_control(
			'link_to',
			[
				'options' => [
					'none' => esc_html__( 'None', 'modulix' ),
					'site_url' => esc_html__( 'Site URL', 'modulix' ),
					'custom' => esc_html__( 'Custom URL', 'modulix' ),
					'file' => esc_html__( 'Media File', 'modulix' ),
				],
				'default' => 'site_url',
			],
			[
				'recursive' => true,
			]
		);

		$this->update_control(
			'caption_source',
			[
				'options' => [
					'none' => esc_html__( 'None', 'modulix' ),
					'attachment' => esc_html__( 'Attachment Caption', 'modulix' ),
				],
			]
		);

		$this->remove_control( 'caption' );

		$this->add_control(
			'change_logo_cta',
			[
				'type' => Controls_Manager::BUTTON,
				'label_block' => true,
				'show_label' => false,
				'button_type' => 'default elementor-button-center',
				'text' => esc_html__( 'Change Site Logo', 'modulix' ),
				'event' => 'elementorProSiteLogo:change',
			],
			[
				'position' => [
					'of' => 'image',
					'type' => 'control',
					'at' => 'after',
				],
			]
		);
	}

	/**
	 * TODO: Remove this method when Elementor Core 3.11.0 is required.
	 * Duplicate of render() method from Elementor\Widget_Image class, so it will use the get_link_url() method.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['image']['url'] ) ) {
			return;
		}

		$has_caption = $this->has_caption( $settings );

		$link = $this->get_link_url( $settings );

		if ( $link ) {
			$this->add_link_attributes( 'link', $link );

			if ( Plugin::elementor()->editor->is_edit_mode() ) {
				$this->add_render_attribute( 'link', 'class', 'elementor-clickable' );
			}

			if ( 'file' === $settings['link_to'] ) {
				$this->add_lightbox_data_attributes( 'link', $settings['image']['id'], $settings['open_lightbox'] );
			}
		} ?>
		<?php if ( $has_caption ) : ?>
		<figure class="wp-caption">
	<?php endif; ?>
		<?php if ( $link ) : ?>
		<a <?php $this->print_render_attribute_string( 'link' ); ?>>
	<?php endif; ?>
		<?php Group_Control_Image_Size::print_attachment_image_html( $settings ); ?>
		<?php if ( $link ) : ?>
		</a>
	<?php endif; ?>
		<?php if ( $has_caption ) : ?>
			<figcaption class="widget-image-caption wp-caption-text"><?php
				echo wp_kses_post( $this->get_caption( $settings ) );
			?></figcaption>
		<?php endif; ?>
		<?php if ( $has_caption ) : ?>
		</figure>
	<?php endif; ?>
		<?php
	}

	protected function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' elementor-widget-' . parent::get_name();
	}

	protected function get_link_url( $settings ) {
		switch ( $settings['link_to'] ) {
			case 'none':
				return false;

			case 'custom':
				return ( ! empty( $settings['link']['url'] ) ) ? $settings['link'] : false;

			case 'site_url':
				return [ 'url' => Plugin::elementor()->dynamic_tags->get_tag_data_content( null, 'site-url' ) ?? '' ];

			default:
				return [ 'url' => $settings['image']['url'] ];
		}
	}

	// TODO: Remove this method when removing the render() method.
	private function has_caption( $settings ) {
		return ( ! empty( $settings['caption_source'] ) && 'none' !== $settings['caption_source'] );
	}

	// TODO: Remove this method when removing the render() method.
	private function get_caption( $settings ) {
		$caption = '';

		if ( ! empty( $settings['caption_source'] ) && 'attachment' === $settings['caption_source'] ) {
			$caption = wp_get_attachment_caption( $settings['image']['id'] );
		}

		return $caption;
	}

	// Get the site logo from the dynamic tag
	private function get_site_logo(): string {
		$site_logo = Plugin::elementor()->dynamic_tags->get_tag_data_content( null, 'site-logo' );
		return $site_logo['url'] ?? Utils::get_placeholder_image_src();
	}
}
