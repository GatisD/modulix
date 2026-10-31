<?php
namespace Modulix\Modules\Library\Classes;
// Modulix: derived from Elementor Pro 4.0.4, modules/library/classes/shortcode.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-24): template IDs, post type, status and read/edit access are checked before rendering.

use Elementor\TemplateLibrary\Source_Local;
use Modulix\Plugin;
use Modulix\Security\DynamicContent;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Shortcode {

	const SHORTCODE = 'elementor-template';

	public function __construct() {
		$this->add_actions();
	}

	public function admin_columns_headers( $defaults ) {
		$defaults['shortcode'] = esc_html__( 'Shortcode', 'modulix' );

		return $defaults;
	}

	public function admin_columns_content( $column_name, $post_id ) {
		if ( 'shortcode' !== $column_name ) {
			return;
		}

		printf(
			'<label class="screen-reader-text" for="%1$s">%2$s</label><textarea class="elementor-shortcode-textarea" id="%1$s" readonly rows="1" onfocus="this.select()">%3$s</textarea>',
			sprintf(
				/* translators: %s: Template ID. */
				esc_attr( 'elementor-template-%s-shortcode' ),
				$post_id // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			),
			sprintf(
				/* translators: %s: Template ID. */
				esc_html__( 'Elementor template shortcode for template %s', 'modulix' ),
				$post_id // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			),
			sprintf(
				/* translators: %s: Shortcode, %d: Template ID. */
				esc_textarea( '[%s id="%d"]' ),
				self::SHORTCODE, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$post_id // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			)
		);
	}

	public function shortcode( $attributes = [] ) {
		if ( empty( $attributes['id'] ) || ! ctype_digit( (string) $attributes['id'] ) ) {
			return '';
		}

		$post_id = (int) $attributes['id'];
		if ( $post_id < 1 ) {
			return '';
		}

		$post = get_post( $post_id );
		$can_read = current_user_can( 'read_post', $post_id );
		$can_edit = current_user_can( 'edit_post', $post_id );
		if ( ! DynamicContent::template_allowed( $post, Source_Local::CPT, $can_read, $can_edit ) ) {
			return '';
		}

		$include_css = false;

		if ( isset( $attributes['css'] ) && 'false' !== $attributes['css'] ) {
			$include_css = (bool) $attributes['css'];
		}

		return Plugin::elementor()->frontend->get_builder_content_for_display( $post_id, $include_css );
	}

	private function add_actions() {
		if ( is_admin() ) {
			add_action( 'manage_' . Source_Local::CPT . '_posts_columns', [ $this, 'admin_columns_headers' ] );
			add_action( 'manage_' . Source_Local::CPT . '_posts_custom_column', [ $this, 'admin_columns_content' ], 10, 2 );
		}

		add_shortcode( self::SHORTCODE, [ $this, 'shortcode' ] );
	}
}
