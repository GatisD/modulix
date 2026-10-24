<?php
namespace Modulix\Modules\Woocommerce\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/widgets/notices.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Plugin;
use Modulix\Modules\Woocommerce\Module;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Notices extends Base_Widget {

	public function get_name() {
		return 'woocommerce-notices';
	}

	public function get_title() {
		return esc_html__( 'WooCommerce Notices', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-woocommerce-notices';
	}

	public function get_keywords() {
		return [ 'woocommerce', 'notices', 'notifications' ];
	}

	public function get_categories() {
		return [ 'woocommerce-elements' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}

	public function get_help_url() {
		return 'https://go.elementor.com/widget-woocommerce-notices-location';
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
		return [ 'widget-woocommerce-notices' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section',
			[
				'label' => esc_html__( 'WooCommerce Notices', 'modulix' ),
			]
		);

		$this->add_control(
			'where_to_appear_notice',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'Drop this widget anywhere on the page or template where you want notices to appear.', 'modulix' ),
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->add_control(
			'site_settings_notice',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => sprintf(
					/* translators: 1: Link opening tag, 2: Link closing tag. */
					esc_html__( 'To change the design of your notices, go to your %1$sWooCommerce Settings%2$s', 'modulix' ),
					'<a href="#" onclick="elementorPro.modules.woocommerce.openSiteSettingsTab( \'settings-woocommerce\', \'section_woocommerce_notices\' );">',
					'</a>'
				),
				'content_classes' => 'elementor-descriptor elementor-descriptor-subtle',
			]
		);

		$this->add_control(
			'one_per_page_notice',
			[
				'type' => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'heading' => esc_html__( 'Note:', 'modulix' ),
				'content' => esc_html__( 'You can only add the Notices widget once per page.', 'modulix' ),
			]
		);

		$this->end_controls_section();
	}

	private function hide_woocommerce_notices() {
		?>
		<style>
			.woocommerce-notices-wrapper,
			.woocommerce-message,
			.woocommerce-error,
			.woocommerce-info {
				display: none;
			}
		</style>
		<?php
	}

	protected function render() {
		if ( Plugin::elementor()->editor->is_edit_mode() || Plugin::elementor()->preview->is_preview_mode() ) {
			$this->render_demo_notice();
		} else {
			$this->hide_woocommerce_notices();
			?>
			<div class="e-woocommerce-notices-wrapper e-woocommerce-notices-wrapper-loading">
				<?php woocommerce_output_all_notices(); ?>
			</div>
			<?php
		}
	}

	protected function render_demo_notice() {
		?>
		<div class="e-notices-demo-notice">
		<?php
		wc_get_template( 'notices/notice.php', [
			'notices' => [
				'0' => [
					'notice' => esc_html__( 'This is an example of a WooCommerce notice. (You won\'t see this while previewing your site.)', 'modulix' ),
					'data' => [],
				],
			],
		] );
		?>
		</div>
		<?php
	}

	public function get_group_name() {
		return 'woocommerce';
	}
}
