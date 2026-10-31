<?php
namespace Modulix\Core\App\Modules\SiteEditor\Data\Endpoints;
// Modulix: derived from Elementor Pro 4.0.4, core/app/modules/site-editor/data/endpoints/template-types.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Plugin;
use Modulix\Core\App\Modules\SiteEditor\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Template_Types extends Base_Endpoint {
	/**
	 * @return string
	 */
	public function get_name() {
		return 'template-types';
	}

	public function get_items( $request ) {
		/** @var Module $site_editor_module */
		$site_editor_module = Plugin::instance()->app->get_component( 'site-editor' );

		return $site_editor_module->get_template_types();
	}
}
