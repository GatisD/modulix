<?php
namespace Modulix\Modules\CustomCode\AdminMenuItems;
// Modulix: derived from Elementor Pro 4.0.4, modules/custom-code/admin-menu-items/custom-code-menu-item.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Admin\Menu\Interfaces\Admin_Menu_Item;
use Elementor\Settings;
use Modulix\Modules\CustomCode\Module as CustomCodeModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Custom_Code_Menu_Item implements Admin_Menu_Item {

	const LICENSE_FEATURE_NAME = 'custom_code';

	public function get_capability(): string {
		return CustomCodeModule::CAPABILITY;
	}

	public function get_label(): string {
		return esc_html__( 'Custom Code', 'modulix' );
	}

	public function get_parent_slug(): string {
		return Settings::PAGE_ID;
	}

	public function get_position(): ?int {
		return null;
	}

	public function is_visible(): bool {
		return true;
	}
}
