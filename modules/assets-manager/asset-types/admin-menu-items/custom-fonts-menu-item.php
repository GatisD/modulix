<?php
namespace Modulix\Modules\AssetsManager\AssetTypes\AdminMenuItems;
// Modulix: derived from Elementor Pro 4.0.4, modules/assets-manager/asset-types/admin-menu-items/custom-fonts-menu-item.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Admin\Menu\Interfaces\Admin_Menu_Item;
use Elementor\Settings;
use Modulix\Modules\AssetsManager\AssetTypes\Fonts_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Custom_Fonts_Menu_Item implements Admin_Menu_Item {
	public function get_capability(): string {
		return Fonts_Manager::CAPABILITY;
	}

	public function get_label(): string {
		return esc_html__( 'Custom Fonts', 'modulix' );
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
