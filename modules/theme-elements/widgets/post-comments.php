<?php
namespace Modulix\Modules\ThemeElements\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-elements/widgets/post-comments.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Modulix\Modules\QueryControl\Module as QueryControlModule;
use Modulix\Modules\ThemeElements\Module;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Post_Comments extends Base {

	public function get_name() {
		return 'post-comments';
	}

	public function get_title() {
		return esc_html__( 'Post Comments', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-comments';
	}

	public function get_categories() {
		return [ 'theme-elements-single' ];
	}

	public function get_keywords() {
		return [ 'comments', 'post', 'response', 'form' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Comments', 'modulix' ),
			]
		);

		$this->add_control(
			'_skin',
			[
				'type' => Controls_Manager::HIDDEN,
			]
		);

		$this->add_control(
			'skin_temp',
			[
				'label' => esc_html__( 'Skin', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => esc_html__( 'Theme Comments', 'modulix' ),
				],
				'description' => esc_html__( 'The Theme Comments skin uses the currently active theme comments design and layout to display the comment form and comments.', 'modulix' ),
			]
		);

		$this->add_control(
			'source_type',
			[
				'label' => esc_html__( 'Source', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					Module::SOURCE_TYPE_CURRENT_POST => esc_html__( 'Current Post', 'modulix' ),
					Module::SOURCE_TYPE_CUSTOM => esc_html__( 'Custom', 'modulix' ),
				],
				'default' => Module::SOURCE_TYPE_CURRENT_POST,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'source_custom',
			[
				'label' => esc_html__( 'Search & Select', 'modulix' ),
				'type' => QueryControlModule::QUERY_CONTROL_ID,
				'label_block' => true,
				'autocomplete' => [
					'object' => QueryControlModule::QUERY_OBJECT_POST,
				],
				'condition' => [
					'source_type' => Module::SOURCE_TYPE_CUSTOM,
				],
			]
		);

		$this->end_controls_section();
	}

	public function render() {
		$settings = $this->get_settings();

		if ( Module::SOURCE_TYPE_CUSTOM === $settings['source_type'] ) {
			$post_id = (int) $settings['source_custom'];
			Plugin::elementor()->db->switch_to_post( $post_id );
		}

		if ( ! comments_open() && ( Plugin::elementor()->preview->is_preview_mode() || Plugin::elementor()->editor->is_edit_mode() ) ) :
			?>
			<div class="elementor-alert elementor-alert-danger" role="alert">
				<span class="elementor-alert-title">
					<?php echo esc_html__( 'Comments are closed.', 'modulix' ); ?>
				</span>
				<span class="elementor-alert-description">
					<?php echo esc_html__( 'Switch on comments from either the discussion box on the WordPress post edit screen or from the WordPress discussion settings.', 'modulix' ); ?>
				</span>
			</div>
			<?php
		else :
			comments_template();
		endif;

		if ( Module::SOURCE_TYPE_CUSTOM === $settings['source_type'] ) {
			Plugin::elementor()->db->restore_current_post();
		}
	}

	public function get_group_name() {
		return 'theme-elements';
	}
}
