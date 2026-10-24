<?php

namespace Modulix\Modules\AssetsManager\AssetTypes\EditorOneMenuItems;
// Modulix: derived from Elementor Pro 4.0.4, modules/assets-manager/asset-types/editor-one-menu-items/editor-one-fonts-menu-item.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Admin\EditorOneMenu\Interfaces\Menu_Item_Interface;
use Elementor\Modules\EditorOne\Classes\Menu_Config;
use Modulix\Modules\AssetsManager\AssetTypes\AdminMenuItems\Custom_Fonts_Menu_Item;
use Modulix\Modules\AssetsManager\AssetTypes\Fonts_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Editor_One_Fonts_Menu_Item extends Custom_Fonts_Menu_Item implements Menu_Item_Interface {

	public function get_position(): int {
		return 10;
	}

	public function get_slug(): string {
		return Fonts_Manager::MENU_SLUG;
	}

	public function get_parent_slug(): string {
		return Menu_Config::ELEMENTOR_MENU_SLUG;
	}

	public function get_label(): string {
		return esc_html__( 'Fonts', 'modulix' );
	}

	public function get_group_id(): string {
		return Menu_Config::CUSTOM_ELEMENTS_GROUP_ID;
	}
}

