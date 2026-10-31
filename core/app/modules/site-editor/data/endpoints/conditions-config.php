<?php
namespace Modulix\Core\App\Modules\SiteEditor\Data\Endpoints;
// Modulix: derived from Elementor Pro 4.0.4, core/app/modules/site-editor/data/endpoints/conditions-config.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\ThemeBuilder\Module as ThemeBuilderModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Conditions_Config extends Base_Endpoint {
	/**
	 * @return string
	 */
	public function get_name() {
		return 'conditions-config';
	}

	public function get_items( $request ) {
		$conditions_manager = ThemeBuilderModule::instance()->get_conditions_manager();

		return $conditions_manager->get_conditions_config();
	}
}
