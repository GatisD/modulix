<?php
namespace Modulix\Modules\DynamicTags\Tags\Base;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/tags/base/license-meta-trait.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait License_Meta_Trait {

	/**
	 * Get the license feature name required for this tag.
	 *
	 * @return string
	 */
	abstract protected function get_required_license_feature();

	/**
	 * Add license metadata to editor config.
	 *
	 * @return array
	 */
	public function get_editor_config() {
		return parent::get_editor_config();
	}
}
