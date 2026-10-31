<?php
namespace Modulix\Modules\ThemeBuilder;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): front-end and template-list parts only. Removed: admin menus and Theme Builder app, new-template dialog fields, promotion notice, finder item, import/export hooks. Widgets limited to Site_Logo.
// Modified by Modulix (2026-10-23, plan 5): theme-post/page/site/archive title widgets added; create_new_dialog_types restored (location labels, legacy Single hidden).
// Modified by Modulix (2026-10-23, plan 6a): Archive_Posts added (the posts module is always present in Modulix).

use Elementor\Controls_Manager;
use Elementor\Core\Base\Document;
use Elementor\TemplateLibrary\Source_Local;
use Modulix\Base\Module_Base;
use Modulix\Core\Utils;
use Modulix\Modules\ThemeBuilder\Classes;
use Modulix\Modules\ThemeBuilder\Documents\Single;
use Modulix\Modules\ThemeBuilder\Documents\Theme_Document;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {

	const ADMIN_LIBRARY_TAB_GROUP = 'theme';

	public static function is_preview() {
		return Plugin::elementor()->preview->is_preview_mode() || is_preview();
	}

	public static function get_public_post_types( $args = [] ) {
		$post_types = Utils::get_public_post_types( $args );

		// Product form WooCommerce are handled separately.
		if ( class_exists( 'woocommerce' ) ) {
			unset( $post_types['product'] );
		}

		return $post_types;
	}

	public function get_name() {
		return 'theme-builder';
	}

	public function get_widgets() {
		return [
			'Site_Logo',
			'Site_Title',
			'Page_Title',
			'Post_Title',
			'Post_Excerpt',
			'Post_Content',
			'Post_Featured_Image',
			'Archive_Title',
			'Archive_Posts',
		];
	}

	/**
	 * @return Classes\Conditions_Manager
	 */
	public function get_conditions_manager() {
		return $this->get_component( 'conditions' );
	}

	/**
	 * @return Classes\Locations_Manager
	 */
	public function get_locations_manager() {
		return $this->get_component( 'locations' );
	}

	/**
	 * @return Classes\Preview_Manager
	 */
	public function get_preview_manager() {
		return $this->get_component( 'preview' );
	}

	/**
	 * @return Classes\Templates_Types_Manager
	 */
	public function get_types_manager() {
		return $this->get_component( 'templates_types' );
	}

	/**
	 * @param $post_id
	 *
	 * @return Theme_Document
	 */
	public function get_document( $post_id ) {
		$document = null;

		try {
			$document = Plugin::elementor()->documents->get( $post_id );
		} catch ( \Exception $e ) {
			// Do nothing.
			unset( $e );
		}

		if ( ! empty( $document ) && ! $document instanceof Theme_Document ) {
			$document = null;
		}

		return $document;
	}

	public function document_config( $config, $post_id ) {
		$document = $this->get_document( $post_id );

		if ( ! $document ) {
			return $config;
		}

		$types_manager      = $this->get_types_manager();
		$conditions_manager = $this->get_conditions_manager();
		$template_type      = $this->get_template_type( $post_id );

		return array_replace_recursive( $config, [
			'theme_builder' => [
				'types' => $types_manager->get_types_config(),
				'conditions' => $conditions_manager->get_conditions_config(),
				'template_conditions' => ( new Classes\Template_Conditions() )->get_config(),
				'is_theme_template' => $this->is_theme_template( $post_id ),
				'settings' => [
					'template_type' => $template_type,
					'location' => $document->get_location(),
					'conditions' => $conditions_manager->get_document_conditions( $document ),
				],
			],
		] );
	}

	public function create_new_dialog_types( $types ) {
		/**
		 * @var Theme_Document[] $document_types
		 */
		foreach ( $types as $type => $label ) {
			$document_type = Plugin::elementor()->documents->get_document_type( $type );
			$instance = new $document_type();

			if ( $instance instanceof Theme_Document && 'section' !== $type ) {
				$types[ $type ] .= $instance->get_location_label();
			}

			if ( Single::class === $document_type ) {
				unset( $types[ $type ] );
			}
		}

		return $types;
	}

	public function register_controls( Controls_Manager $controls_manager ) {
		$controls_manager->register( new Classes\Conditions_Repeater() );
		$controls_manager->register( new Classes\Control_Media_Preview() );
	}

	public function admin_head() {
		$current_screen = get_current_screen();
		if ( $current_screen && in_array( $current_screen->id, [ 'elementor_library', 'edit-elementor_library' ] ) ) {
			// For column type (Supported/Unsupported).
			$this->get_locations_manager()->register_locations();
		}
	}

	public function admin_columns_content( $column_name, $post_id ) {
		if ( 'elementor_library_type' === $column_name ) {
			/** @var Document $document */
			$document = Plugin::elementor()->documents->get( $post_id );

			if ( $document instanceof Theme_Document ) {
				$location_label = $document->get_location_label();

				if ( $location_label ) {
					echo ' - ' . esc_html( $location_label );
				}
			}
		}
	}

	public function get_template_type( $post_id ) {
		return Source_Local::get_template_type( $post_id );
	}

	public function is_theme_template( $post_id ) {
		$document = Plugin::elementor()->documents->get( $post_id );

		return $document instanceof Theme_Document;
	}

	public function on_elementor_editor_init() {
		Plugin::elementor()->common->add_template( __DIR__ . '/views/panel-template.php' );
	}

	/**
	 * Add attributes to the document wrapper element.
	 *
	 * @param array $attributes - The document's wrapper element attributes.
	 * @param Document $document
	 *
	 * @return array
	 */
	public function add_document_attributes( array $attributes, Document $document ): array {
		$attributes['data-elementor-post-type'] = $document->get_post()->post_type;

		return $attributes;
	}

	public function __construct() {
		parent::__construct();

		require __DIR__ . '/api.php';

		$this->add_component( 'theme_support', new Classes\Theme_Support() );
		$this->add_component( 'conditions', new Classes\Conditions_Manager() );
		$this->add_component( 'templates_types', new Classes\Templates_Types_Manager() );
		$this->add_component( 'preview', new Classes\Preview_Manager() );
		$this->add_component( 'locations', new Classes\Locations_Manager() );

		add_action( 'elementor/controls/register', [ $this, 'register_controls' ] );
		add_action( 'elementor/editor/init', [ $this, 'on_elementor_editor_init' ] );
		add_filter( 'elementor/document/config', [ $this, 'document_config' ], 10, 2 );
		add_filter( 'elementor/document/wrapper_attributes', [ $this, 'add_document_attributes' ], 10, 2 );

		// Admin
		add_action( 'admin_head', [ $this, 'admin_head' ] );
		add_action( 'manage_' . Source_Local::CPT . '_posts_custom_column', [ $this, 'admin_columns_content' ], 10, 2 );
		add_filter( 'elementor/template-library/create_new_dialog_types', [ $this, 'create_new_dialog_types' ] );
	}
}
