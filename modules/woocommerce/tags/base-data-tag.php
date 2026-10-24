<?php
namespace Modulix\Modules\Woocommerce\Tags;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/tags/base-data-tag.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\DynamicTags\Tags\Base\Data_Tag;
use Modulix\Modules\DynamicTags\Tags\Base\License_Meta_Trait;
use Modulix\Modules\Woocommerce\Module;
use Modulix\Modules\Woocommerce\Tags\Traits\Tag_Product_Id;
use Modulix\Modules\Woocommerce\Traits\Product_Id_Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Base_Data_Tag extends Data_Tag {

	use Tag_Product_Id;
	use Product_Id_Trait;
	use License_Meta_Trait;

	public function get_group() {
		return Module::WOOCOMMERCE_GROUP;
	}

	protected function get_required_license_feature() {
		return Module::DYNAMIC_TAGS_LICENSE_FEATURE_NAME;
	}

	public function get_editor_config() {
		$conf = parent::get_editor_config();
		$conf['force_convert_to_atomic'] = true;
		return $conf;
	}
}
