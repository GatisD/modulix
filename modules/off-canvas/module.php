<?php
namespace Modulix\Modules\OffCanvas;
// Modulix: derived from Elementor Pro 4.0.4, modules/off-canvas/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Experiments\Manager;
use Modulix\Base\Module_Base;
use Modulix\Modules\OffCanvas\Widgets\Off_Canvas;
use Modulix\Plugin;
use Elementor\Core\DynamicTags\Manager as DynamicTagsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {
	const FEATURE_ID = 'off-canvas';

	public function __construct() {
		parent::__construct();

		add_action( 'elementor/dynamic_tags/register', [ $this, 'register_tag' ] );
		add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_styles' ] );
	}

	public function get_name() {
		return self::FEATURE_ID;
	}

	protected function get_widgets() {
		return [ Off_Canvas::WIDGET_ID ];
	}

	public static function is_active() {
		return Plugin::elementor()->experiments->is_feature_active( 'nested-elements' );
	}

	public function register_tag( DynamicTagsManager $dynamic_tags ) {
		$tag = __NAMESPACE__ . '\Tag';

		$dynamic_tags->register( new $tag() );
	}

	/**
	 * Get the base URL for assets.
	 *
	 * @return string
	 */
	public function get_assets_base_url(): string {
		return MODULIX_URL;
	}

	/**
	 * Register styles.
	 *
	 * At build time, Elementor compiles `/modules/off-canvas/assets/scss/frontend.scss`
	 * to `/assets/css/widget-off-canvas.min.css`.
	 *
	 * @return void
	 */
	public function register_styles() {
		wp_register_style(
			'widget-off-canvas',
			$this->get_css_assets_url( 'widget-off-canvas', null, true, true ),
			[ 'elementor-frontend' ],
			MODULIX_VERSION
		);
	}
}
