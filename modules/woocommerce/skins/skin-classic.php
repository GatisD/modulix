<?php
namespace Modulix\Modules\Woocommerce\Skins;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/skins/skin-classic.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Elementor\Skin_Base;
use Elementor\Widget_Base;
use Modulix\Modules\Woocommerce\Module;
use Modulix\Modules\Woocommerce\Widgets\Products;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Skin_Classic
 * @property Products $parent
 */
class Skin_Classic extends Skin_Base {

	public function get_id() {
		return 'classic';
	}

	public function get_title() {
		return esc_html__( 'Classic', 'modulix' );
	}

	protected function _register_controls_actions() {
		add_action( 'elementor/element/wc-products/section_layout/after_section_start', [ $this, 'register_controls' ] );
	}

	public function register_controls( Widget_Base $widget ) {
		$this->parent = $widget;

		$this->add_control(
			'columns',
			[
				'label' => esc_html__( 'Columns', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'1' => esc_html__( '1', 'modulix' ),
					'2' => esc_html__( '2', 'modulix' ),
					'3' => esc_html__( '3', 'modulix' ),
					'4' => esc_html__( '4', 'modulix' ),
					'5' => esc_html__( '5', 'modulix' ),
					'6' => esc_html__( '6', 'modulix' ),
				],
				'default' => '4',
			]
		);
	}

	public function render() {
		$this->parent->query_posts();

		/** @var \WP_Query $query */
		$query = $this->parent->get_query();

		if ( ! $query->have_posts() ) {
			return;
		}

		global $woocommerce_loop;

		$woocommerce_loop['columns'] = (int) $this->get_instance_value( 'columns' );

		Module::instance()->add_products_post_class_filter();

		echo '<div class="woocommerce columns-' . esc_attr( $woocommerce_loop['columns'] ) . '">';

		woocommerce_product_loop_start();

		while ( $query->have_posts() ) {
			$query->the_post();

			wc_get_template_part( 'content', 'product' );
		}

		woocommerce_product_loop_end();

		woocommerce_reset_loop();

		wp_reset_postdata();

		echo '</div>';

		Module::instance()->remove_products_post_class_filter();
	}
}
