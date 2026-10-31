<?php
namespace Modulix\Core\App\Modules\SiteEditor;
// Modulix: derived from Elementor Pro 4.0.4, core/app/modules/site-editor/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-24): licensed local routes only; feature lock, experiment, promotion menu and Editor One dependencies removed.

use Elementor\Core\Base\Module as BaseModule;
use Elementor\Core\Common\Modules\Ajax\Module as Ajax;
use Elementor\Core\Frontend\Render_Mode_Manager;
use Elementor\TemplateLibrary\Source_Local;
use Modulix\App;
use Modulix\Core\App\Modules\SiteEditor\Data\Controller;
use Modulix\Modules\ThemeBuilder\Module as ThemeBuilderModule;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Site Editor Module
 *
 * Responsible for initializing Elementor Pro App functionality
 */
class Module extends BaseModule {
	/**
	 * Get name.
	 *
	 * @access public
	 *
	 * @return string
	 */
	public function get_name() {
		return 'site-editor';
	}

	/**
	 * @throws \Exception
	 */
	public function get_template_types() {
		// Same as admin menu capabilities.
		if ( ! current_user_can( 'publish_posts' ) ) {
			throw new \Exception( 'Access denied' );
		}

		$document_types = Plugin::elementor()->documents->get_document_types( [
			'support_site_editor' => true,
		] );

		// Keep 404 at end of array.
		$error_404 = $document_types['error-404'];
		unset( $document_types['error-404'] );
		$document_types['error-404'] = $error_404;

		// Currently the `single` itself is not supported in site editor.
		// Don't use `support_site_editor=false` in order to support documents that extend it.
		unset( $document_types['single'] );

		$types = [];

		foreach ( $document_types as $type => $class ) {
			$types[] = $class::get_site_editor_config();
		}

		return App::sanitizeTemplateTypeConfig( $types );
	}

	/**
	 * Register ajax actions.
	 *
	 * @access public
	 *
	 * @param Ajax $ajax
	 */
	public function register_ajax_actions( Ajax $ajax ) {
		$ajax->register_ajax_action( 'app_site_editor_template_types', [ $this, 'get_template_types' ] );
	}

	/**
	 * @param Render_Mode_Manager $manager
	 *
	 * @throws \Exception
	 */
	public function register_render_mode( Render_Mode_Manager $manager ) {
		$manager->register_render_mode( Render_Mode_Template_Preview::class );
	}

	protected function get_init_settings() {
		$legacy_url = add_query_arg(
			'tabs_group',
			ThemeBuilderModule::ADMIN_LIBRARY_TAB_GROUP,
			admin_url( Source_Local::ADMIN_MENU_SLUG )
		);

		return App::siteEditorConfig( App::mode(), $legacy_url );
	}

	/**
	 * Get site editor url.
	 *
	 * @return string
	 */
	private function get_site_editor_url() : string {
		return Plugin::elementor()->app->get_base_url() . '#/site-editor';
	}

	private function add_finder_item( array $categories ) {
		// Replace the old theme builder "create-new" link with the new site-editor.
		$categories['create']['items']['theme-template'] = [
			'title' => __( 'Add New Theme Template', 'modulix' ),
			'icon' => 'plus-circle-o',
			'url' => $this->get_site_editor_url() . '/add-new',
			'keywords' => [ 'template', 'theme', 'new', 'create' ],
		];

		return $categories;
	}

	public function __construct() {
		Plugin::elementor()->data_manager->register_controller( Controller::class );

		add_action( 'elementor/ajax/register_actions', [ $this, 'register_ajax_actions' ], 11 /* Override core actions */ );
		add_action( 'elementor/frontend/render_mode/register', [ $this, 'register_render_mode' ] );

		add_filter( 'elementor/finder/categories', function ( array $categories ) {
			return $this->add_finder_item( $categories );
		}, 11 /* After old theme builder */ );

	}
}
