<?php
namespace Modulix\Modules\DynamicTags\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/internal-url.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-24): non-public content requires read access, taxonomy URLs require viewable taxonomies, and author URLs require public posts or user-listing access.

use Elementor\Controls_Manager;
use Modulix\Modules\DynamicTags\Tags\Base\Pro_Data_Tag;
use Modulix\Modules\DynamicTags\Module;
use Modulix\Modules\QueryControl\Module as QueryModule;
use Modulix\Security\DynamicContent;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Internal_URL extends Pro_Data_Tag {

	public function get_name() {
		return 'internal-url';
	}

	public function get_group() {
		return Module::SITE_GROUP;
	}

	public function get_categories() {
		return [ Module::URL_CATEGORY ];
	}

	public function get_title() {
		return esc_html__( 'Internal URL', 'modulix' );
	}

	public function get_panel_template() {
		return ' ({{ url }})';
	}

	/**
	 * @since 3.6.0
	 * @deprecated 3.8.0 Use `On_Import_Trait::on_import_update_dynamic_content()` instead.
	 *
	 * Remove in the future.
	 */
	public static function on_import_replace_dynamic_content( $config, $map_old_new_post_ids ) {
		if ( isset( $config['settings']['post_id'] ) ) {
			$config['settings']['post_id'] = $map_old_new_post_ids[ $config['settings']['post_id'] ];
		}

		return $config;
	}

	public function get_value( array $options = [] ) {
		$settings = $this->get_settings();

		$type = $settings['type'];
		$url = '';

		if ( 'post' === $type && ! empty( $settings['post_id'] ) ) {
			$post_id = (int) $settings['post_id'];
			$post = get_post( $post_id );
			if ( DynamicContent::linked_post_allowed( $post, current_user_can( 'read_post', $post_id ) ) ) {
				$url = get_permalink( $post );
			}
		} elseif ( 'taxonomy' === $type && ! empty( $settings['taxonomy_id'] ) ) {
			$term = get_term( (int) $settings['taxonomy_id'] );
			if ( $term && ! is_wp_error( $term ) ) {
				$taxonomy = get_taxonomy( $term->taxonomy );
				if ( $taxonomy && is_taxonomy_viewable( $taxonomy ) ) {
					$url = get_term_link( $term );
				}
			}
		} elseif ( 'attachment' === $type && ! empty( $settings['attachment_id'] ) ) {
			$attachment_id = (int) $settings['attachment_id'];
			$attachment = get_post( $attachment_id );
			$parent = $attachment && ! empty( $attachment->post_parent ) ? get_post( $attachment->post_parent ) : null;
			if ( DynamicContent::linked_post_allowed( $attachment, current_user_can( 'read_post', $attachment_id ), $parent ) ) {
				$url = get_attachment_link( $attachment_id );
			}
		} elseif ( 'author' === $type && ! empty( $settings['author_id'] ) ) {
			$author_id = (int) $settings['author_id'];
			$author = get_userdata( $author_id );
			$public_post_types = get_post_types( [ 'public' => true ], 'names' );
			$has_published_posts = $author && count_user_posts( $author_id, $public_post_types, true ) > 0;
			if ( DynamicContent::author_link_allowed( $author ?: null, current_user_can( 'list_users' ), $has_published_posts ) ) {
				$url = get_author_posts_url( $author_id );
			}
		}

		if ( ! is_wp_error( $url ) ) {
			return esc_url_raw( $url );
		}

		return '';
	}

	protected function register_controls() {
		$this->add_control( 'type', [
			'label' => esc_html__( 'Type', 'modulix' ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'post' => esc_html__( 'Content', 'modulix' ),
				'taxonomy' => esc_html__( 'Taxonomy', 'modulix' ),
				'attachment' => esc_html__( 'Media', 'modulix' ),
				'author' => esc_html__( 'Author', 'modulix' ),
			],
		] );

		$this->add_control( 'post_id', [
			'label' => esc_html__( 'Search & Select', 'modulix' ),
			'type' => QueryModule::QUERY_CONTROL_ID,
			'options' => [],
			'label_block' => true,
			'autocomplete' => [
				'object' => QueryModule::QUERY_OBJECT_POST,
				'display' => 'detailed',
				'query' => [
					'post_type' => 'any',
				],
			],
			'condition' => [
				'type' => 'post',
			],
		] );

		$this->add_control( 'taxonomy_id', [
			'label' => esc_html__( 'Search & Select', 'modulix' ),
			'type' => QueryModule::QUERY_CONTROL_ID,
			'options' => [],
			'label_block' => true,
			'autocomplete' => [
				'object' => QueryModule::QUERY_OBJECT_TAX,
				'display' => 'detailed',
				'query' => [
					'taxonomy' => QueryModule::get_viewable_taxonomy_names(),
				],
			],
			'condition' => [
				'type' => 'taxonomy',
			],
		] );

		$this->add_control( 'attachment_id', [
			'label' => esc_html__( 'Search & Select', 'modulix' ),
			'type' => QueryModule::QUERY_CONTROL_ID,
			'options' => [],
			'label_block' => true,
			'autocomplete' => [
				'object' => QueryModule::QUERY_OBJECT_ATTACHMENT,
				'display' => 'detailed',
			],
			'condition' => [
				'type' => 'attachment',
			],
		] );

		$this->add_control( 'author_id', [
			'label' => esc_html__( 'Search & Select', 'modulix' ),
			'type' => QueryModule::QUERY_CONTROL_ID,
			'options' => [],
			'label_block' => true,
			'autocomplete' => [
				'object' => QueryModule::QUERY_OBJECT_AUTHOR,
				'display' => 'detailed',
			],
			'condition' => [
				'type' => 'author',
			],
		] );
	}
}
