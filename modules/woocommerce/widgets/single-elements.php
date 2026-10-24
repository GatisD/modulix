<?php
namespace Modulix\Modules\Woocommerce\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/widgets/single-elements.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Single_Elements extends Base_Widget {

	private array $allowed_functions;

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
		$this->init_allowed_functions();
	}

	public function get_name() {
		return 'wc-single-elements';
	}

	public function get_title() {
		return esc_html__( 'Woo - Single Elements', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-woocommerce';
	}

	/* Deprecated Widget */
	public function show_in_panel() {
		return false;
	}

	/**
	 * @return void
	 */
	private function init_allowed_functions(): void {
		$this->allowed_functions = [
			'' => '— ' . esc_html__( 'Select', 'modulix' ) . ' —',
			'woocommerce_output_product_data_tabs' => esc_html__( 'Data Tabs', 'modulix' ),
			'woocommerce_template_single_title' => esc_html__( 'Title', 'modulix' ),
			'woocommerce_template_single_rating' => esc_html__( 'Rating', 'modulix' ),
			'woocommerce_template_single_price' => esc_html__( 'Price', 'modulix' ),
			'woocommerce_template_single_excerpt' => esc_html__( 'Excerpt', 'modulix' ),
			'woocommerce_template_single_meta' => esc_html__( 'Meta', 'modulix' ),
			'woocommerce_template_single_sharing' => esc_html__( 'Sharing', 'modulix' ),
			'woocommerce_show_product_sale_flash' => esc_html__( 'Sale Flash', 'modulix' ),
			'woocommerce_product_additional_information_tab' => esc_html__( 'Additional Information Tab', 'modulix' ),
			'woocommerce_upsell_display' => esc_html__( 'Upsell', 'modulix' ),
			'wc_get_stock_html' => esc_html__( 'Stock Status', 'modulix' ),
		];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_product',
			[
				'label' => esc_html__( 'Element', 'modulix' ),
			]
		);

		$this->add_control(
			'element',
			[
				'label' => esc_html__( 'Element', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => $this->allowed_functions,
			]
		);

		$this->end_controls_section();
	}

	public function remove_description_tab( $tabs ) {
		unset( $tabs['description'] );

		return $tabs;
	}

	private function get_element() {
		global $product;
		$product = $this->get_product();

		$settings = $this->get_settings();
		$html = '';

		switch ( $settings['element'] ) {
			case '':
				break;

			case 'wc_get_stock_html':
				$html = wc_get_stock_html( $product );
				break;

			case 'woocommerce_output_product_data_tabs':
				add_filter( 'woocommerce_product_tabs', [ $this, 'remove_description_tab' ], 11 /* after default tabs*/ );
				ob_start();
				woocommerce_output_product_data_tabs();
				// Wrap with the internal woocommerce `product` class
				$html = '<div class="product">' . ob_get_clean() . '</div>';
				remove_filter( 'woocommerce_product_tabs', [ $this, 'remove_description_tab' ], 11 );
				break;

			case 'woocommerce_template_single_rating':
				$is_edit_mode = Plugin::elementor()->editor->is_edit_mode();

				if ( 'no' === get_option( 'woocommerce_enable_review_rating' ) ) {
					if ( $is_edit_mode ) {
						$html = esc_html__( 'Admin Notice:', 'modulix' ) . ' ' . esc_html__( 'Please enable the Review Rating', 'modulix' );
					}
					break;
				}

				ob_start();
				woocommerce_template_single_rating();
				$html = ob_get_clean();
				if ( '' === $html && $is_edit_mode ) {
					$html = esc_html__( 'Admin Notice:', 'modulix' ) . ' ' . esc_html__( 'No Rating Reviews', 'modulix' );
				}
				break;

			default:
				if ( is_callable( $settings['element'] ) && array_key_exists( $settings['element'], $this->allowed_functions ) ) {
					$html = call_user_func( $settings['element'] );
				}
		}

		return $html;
	}

	protected function render() {
		// PHPCS - the method get_content is safe.
		echo $this->get_element(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function render_plain_content() {}

	public function get_group_name() {
		return 'woocommerce';
	}
}
