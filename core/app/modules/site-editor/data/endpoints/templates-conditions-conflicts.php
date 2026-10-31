<?php
namespace Modulix\Core\App\Modules\SiteEditor\Data\Endpoints;
// Modulix: derived from Elementor Pro 4.0.4, core/app/modules/site-editor/data/endpoints/templates-conditions-conflicts.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Plugin;
use Modulix\Modules\ThemeBuilder\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Templates_Conditions_Conflicts extends Base_Endpoint {
	/**
	 * @return string
	 */
	public function get_name() {
		return 'templates-conditions-conflicts';
	}

	public function get_items( $request ) {
		/** @var Module $theme_builder */
		$theme_builder = Plugin::instance()->modules_manager->get_modules( 'theme-builder' );

		return $theme_builder
			->get_conditions_manager()
			->get_conditions_conflicts( intval( $request['post_id'] ), $request['condition'] );
	}
}
