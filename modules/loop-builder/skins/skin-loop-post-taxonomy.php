<?php
namespace Modulix\Modules\LoopBuilder\Skins;
// Modulix: derived from Elementor Pro 4.0.4, modules/loop-builder/skins/skin-loop-post-taxonomy.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\LoopBuilder\Module;
use Modulix\Modules\LoopBuilder\Providers\Taxonomy_Loop_Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Skin_Loop_Post_Taxonomy extends Skin_Loop_Taxonomy_Base {

	protected $post_type = 'post';

	public function get_id() {
		return Module::LOOP_POST_TAXONOMY_SKIN_ID;
	}

	public function get_title() {
		return esc_html__( 'Post Taxonomy', 'modulix' );
	}

	protected function render_before_loop( $template_id ) {
		$widget = $this->parent;

		$this->enqueue_loop_document_css_meta( $template_id );
		$this->maybe_add_load_more_wrapper_class();
		$widget->before_skin_render();
		$this->render_loop_header();
	}

	protected function get_default_source_option() {
		return Taxonomy_Loop_Provider::POST_CATEGORY_TAXONOMY;
	}
}
