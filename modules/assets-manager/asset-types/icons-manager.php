<?php
namespace Modulix\Modules\AssetsManager\AssetTypes;
// Modulix: derived from Elementor Pro 4.0.4, modules/assets-manager/asset-types/icons-manager.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Admin\Menu\Admin_Menu_Manager;
use Elementor\Modules\EditorOne\Classes\Menu_Data_Provider;
use Elementor\Plugin;
use Modulix\Base\Editor_One_Trait;
use Modulix\Modules\AssetsManager\AssetTypes\AdminMenuItems\Custom_Icons_Menu_Item;
use Modulix\Modules\AssetsManager\AssetTypes\EditorOneMenuItems\Editor_One_Icons_Menu_Item;
use Modulix\Modules\AssetsManager\Classes;
use Modulix\Security\PostTypeCapabilities;
use Elementor\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Icons_Manager {
	use Editor_One_Trait;

	const CAPABILITY = 'manage_options';

	const CPT = 'elementor_icons';

	const MENU_SLUG = 'edit.php?post_type=' . self::CPT;

	private $post_type_object;

	private $enqueued_fonts = [];

	protected $icon_types = [];

	/**
	 * get a font type object for a given type
	 *
	 * @param null $type
	 *
	 * @return array|bool|\Modulix\Modules\AssetsManager\Classes\Font_Base
	 */
	public function get_icon_type_object( $type = null ) {
		if ( null === $type ) {
			return $this->icon_types;
		}

		if ( isset( $this->icon_types[ $type ] ) ) {
			return $this->icon_types[ $type ];
		}

		return false;
	}

	/**
	 * Add a font type to the font manager
	 *
	 * @param string            $icon_type
	 * @param Classes\Assets_Base $instance
	 */
	public function add_icon_type( $icon_type, $instance ) {
		$this->icon_types[ $icon_type ] = $instance;
	}

	/**
	 * Register elementor icon set custom post type
	 */
	public function register_post_type() {
		$labels = [
			'name' => _x( 'Custom Icons', 'CPT Name', 'modulix' ),
			'singular_name' => _x( 'Icon Set', 'CPT Singular Name', 'modulix' ),
			'add_new' => esc_html__( 'Add New', 'modulix' ),
			'add_new_item' => esc_html__( 'Add New Icon Set', 'modulix' ),
			'edit_item' => esc_html__( 'Edit Icon Set', 'modulix' ),
			'new_item' => esc_html__( 'New Icon Set', 'modulix' ),
			'all_items' => esc_html__( 'All Icons', 'modulix' ),
			'view_item' => esc_html__( 'View Icon', 'modulix' ),
			'search_items' => esc_html__( 'Search Icon Set', 'modulix' ),
			'not_found' => esc_html__( 'No icons found', 'modulix' ),
			'not_found_in_trash' => esc_html__( 'No icons found in trash', 'modulix' ),
			'parent_item_colon' => '',
			'menu_name' => _x( 'Custom Icons', 'CPT Menu Name', 'modulix' ),
		];

		$args = [
			'labels' => $labels,
			'public' => false,
			'rewrite' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			'capability_type' => 'post',
			'capabilities' => PostTypeCapabilities::restricted( self::CPT, self::CAPABILITY ),
			'map_meta_cap' => true,
			'hierarchical' => false,
			'supports' => [ 'title' ],
		];

		$this->post_type_object = register_post_type( self::CPT, $args );
	}

	public function post_updated_messages( $messages ) {
		$messages[ self::CPT ] = [
			0 => '', // Unused. Messages start at index 1.
			1 => esc_html__( 'Icon Set updated.', 'modulix' ),
			2 => esc_html__( 'Custom field updated.', 'modulix' ),
			3 => esc_html__( 'Custom field deleted.', 'modulix' ),
			4 => esc_html__( 'Icon Set updated.', 'modulix' ),
			/* translators: %s: Date and time of the revision. */
			5 => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Icon Set restored to revision from %s', 'modulix' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => esc_html__( 'Icon Set saved.', 'modulix' ),
			7 => esc_html__( 'Icon Set saved.', 'modulix' ),
			8 => esc_html__( 'Icon Set submitted.', 'modulix' ),
			9 => esc_html__( 'Icon Set updated.', 'modulix' ),
			10 => esc_html__( 'Icon Set draft updated.', 'modulix' ),
		];

		return $messages;
	}

	/**
	 * Add Font manager link to admin menu
	 */
	private function register_admin_menu( Admin_Menu_Manager $admin_menu_manager ) {
		$admin_menu_manager->register( static::MENU_SLUG, new Custom_Icons_Menu_Item() );
	}

	public function redirect_admin_old_page_to_new() {
		if ( ! empty( $_GET['page'] ) && 'elementor_custom_icons' === $_GET['page'] ) {
			wp_safe_redirect( admin_url( static::MENU_SLUG ) );
			die;
		}
	}

	/**
	 * Clean up admin Font manager admin listing
	 */
	public function clean_admin_listing_page() {
		global $typenow;

		if ( self::CPT !== $typenow ) {
			return;
		}

		add_filter( 'months_dropdown_results', '__return_empty_array' );
		add_filter( 'screen_options_show_screen', '__return_false' );
	}

	public function post_row_actions( $actions, $post ) {
		if ( self::CPT !== $post->post_type ) {
			return $actions;
		}

		unset( $actions['inline hide-if-no-js'] );

		return $actions;
	}

	public function add_finder_item( array $categories ) {
		$categories['settings']['items']['custom-icons'] = [
			'title' => esc_html__( 'Custom Icons', 'modulix' ),
			'icon' => 'favorite',
			'url' => admin_url( static::MENU_SLUG ),
			'keywords' => [ 'custom', 'icons', 'elementor' ],
		];

		return $categories;
	}

	protected function actions() {
		add_action( 'init', [ $this, 'register_post_type' ] );

		if ( is_admin() ) {
			add_action( 'init', [ $this, 'redirect_admin_old_page_to_new' ] );

			add_action( 'elementor/admin/menu/register', function ( Admin_Menu_Manager $admin_menu_manager ) {
				if ( $this->is_editor_one_active() ) {
					return;
				}

				$this->register_admin_menu( $admin_menu_manager );
			} );

			// TODO: BC - Remove after `Admin_Menu_Manager` will be the standard.
			add_action( 'admin_menu', function () {
				if ( did_action( 'elementor/admin/menu/register' ) ) {
					return;
				}

				$menu_title = _x( 'Custom Icons', 'Elementor Font', 'modulix' );

				add_submenu_page(
					Settings::PAGE_ID,
					$menu_title,
					$menu_title,
					self::CAPABILITY,
					static::MENU_SLUG
				);
			}, 50 );

			add_action( 'admin_head', [ $this, 'clean_admin_listing_page' ] );

			add_action( 'elementor/editor-one/menu/register', function ( Menu_Data_Provider $menu_data_provider ) {
				$menu_data_provider->register_menu( new Editor_One_Icons_Menu_Item() );
			} );
		}

		// TODO: Maybe just ignore all of those when the user can't use custom icons?
		add_filter( 'post_updated_messages', [ $this, 'post_updated_messages' ] );
		add_filter( 'post_row_actions', [ $this, 'post_row_actions' ], 10, 2 );

		add_filter( 'elementor/finder/categories', [ $this, 'add_finder_item' ] );

		add_filter( 'elementor/editor-one/admin-edit-post-types', function ( array $post_types ) {
			$post_types[] = self::CPT;

			return $post_types;
		} );

		/**
		 * Elementor icons manager loaded.
		 *
		 * Fires after the icons manager was fully loaded and instantiated.
		 *
		 * @since 2.0.0
		 *
		 * @param Fonts_Manager $this An instance of icons manager.
		 */
		do_action( 'elementor_pro/icons_manager_loaded', $this );
	}

	/**
	 * Fonts_Manager constructor.
	 */
	public function __construct() {
		$this->actions();
		$this->add_icon_type( 'custom', new Icons\Custom_Icons() );
		$this->add_icon_type( 'font-awesome-pro', new Icons\Font_Awesome_Pro() );
	}
}
