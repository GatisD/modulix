<?php
namespace Modulix\Modules\ThemeBuilder\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/widgets/category-image.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Widget_Image;
use Modulix\Base\Base_Widget_Trait;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Category_Image extends Widget_Image {

	use Base_Widget_Trait;

	public function get_name() {
		return 'woocommerce-category-image';
	}

	public function get_title() {
		return esc_html__( 'Category Image', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-featured-image';
	}

	public function get_categories() {
		return [ 'woocommerce-elements', 'woocommerce-elements-single' ];
	}

	public function get_keywords() {
		return [ 'woocommerce', 'category', 'image', 'thumbnail' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}

	protected function register_controls() {
		parent::register_controls();

		$this->update_control(
			'image',
			[
				'dynamic' => [
					'default' => Plugin::elementor()->dynamic_tags->tag_data_to_tag_text( null, 'woocommerce-category-image-tag' ),
				],
			],
			[
				'recursive' => true,
			]
		);
	}

	protected function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' elementor-widget-' . parent::get_name();
	}

	public function get_group_name() {
		return 'woocommerce';
	}
}
