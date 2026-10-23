<?php
namespace Modulix\Modules\Library\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/library/widgets/template.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): template_id uses core select2 with the published template list (Pro's AJAX query control is not lifted); same setting key and value.

use Elementor\Core\Base\Document;
use Modulix\Base\Base_Widget;
use Elementor\Controls_Manager;
use Modulix\TemplateOptions;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Template extends Base_Widget {

	public function get_name() {
		return 'template';
	}

	public function get_title() {
		return esc_html__( 'Template', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-document-file';
	}

	public function get_keywords() {
		return [ 'elementor', 'template', 'library', 'block', 'page' ];
	}

	public function is_reload_preview_required() {
		return false;
	}

	/**
	 * @since 3.6.0
	 *
	 * @deprecated 3.8.0
	 * On_Import_Trait::on_import_update_dynamic_content() should be used instead.
	 * Remove in the future.
	 */
	public static function on_import_replace_dynamic_content( $config, $map_old_new_post_ids ) {
		if ( isset( $config['settings']['template_id'] ) ) {
			$config['settings']['template_id'] = $map_old_new_post_ids[ $config['settings']['template_id'] ];
		}

		return $config;
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_template',
			[
				'label' => esc_html__( 'Template', 'modulix' ),
			]
		);

		$document_types = Plugin::elementor()->documents->get_document_types( [
			'show_in_library' => true,
		] );

		$this->add_control(
			'template_id',
			[
				'label' => esc_html__( 'Choose Template', 'modulix' ),
				'type' => Controls_Manager::SELECT2,
				'label_block' => true,
				// Built only for editor requests (admin page, admin-ajax, REST): a front-end page renders from the
				// saved ID and needs no list. editor->is_edit_mode() is false in the editor's AJAX config request.
				'options' => ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) )
					? $this->get_template_options( $document_types ) : [],
			]
		);

		$this->end_controls_section();
	}

	private function get_template_options( array $document_types ): array {
		$posts = get_posts( [
			'post_type' => 'elementor_library',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'meta_query' => [
				[
					'key' => Document::TYPE_META_KEY,
					'value' => array_keys( $document_types ),
					'compare' => 'IN',
				],
			],
		] );
		$types = [];
		foreach ( $posts as $post ) {
			$types[ $post->ID ] = get_post_meta( $post->ID, Document::TYPE_META_KEY, true );
		}
		$labels = [];
		foreach ( $document_types as $type => $class_name ) {
			$labels[ $type ] = $class_name::get_title();
		}

		return TemplateOptions::from_posts( $posts, $types, $labels );
	}

	protected function render() {
		$template_id = $this->get_settings( 'template_id' );

		if ( empty( $template_id ) ) {
			return;
		}

		if ( 'publish' !== get_post_status( $template_id ) ) {
			return;
		}

		?>
		<div class="elementor-template">
			<?php
				// PHPCS - should not be escaped.
				echo Plugin::elementor()->frontend->get_builder_content_for_display( $template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
		<?php
	}

	public function render_plain_content() {}
}
