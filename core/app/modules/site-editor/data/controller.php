<?php
namespace Modulix\Core\App\Modules\SiteEditor\Data;
// Modulix: derived from Elementor Pro 4.0.4, core/app/modules/site-editor/data/controller.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Plugin;
use Elementor\Data\Base\Controller as Controller_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Controller extends Controller_Base {
	public function get_name() {
		return 'site-editor';
	}

	public function register_endpoints() {
		$this->register_endpoint( Endpoints\Templates::class );
		$this->register_endpoint( Endpoints\Conditions_Config::class );
		$this->register_endpoint( Endpoints\Templates_Conditions::class );
		$this->register_endpoint( Endpoints\Templates_Conditions_Conflicts::class );
	}

	public function get_permission_callback( $request ) {
		return Plugin::elementor()->kits_manager->get_active_kit()->is_editable_by_current_user();
	}
}
