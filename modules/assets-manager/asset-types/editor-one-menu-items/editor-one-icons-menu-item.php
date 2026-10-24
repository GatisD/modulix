<?php

namespace Modulix\Modules\AssetsManager\AssetTypes\EditorOneMenuItems;
// Modulix: derived from Elementor Pro 4.0.4, modules/assets-manager/asset-types/editor-one-menu-items/editor-one-icons-menu-item.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Admin\EditorOneMenu\Interfaces\Menu_Item_Interface;
use Elementor\Modules\EditorOne\Classes\Menu_Config;
use Modulix\Modules\AssetsManager\AssetTypes\AdminMenuItems\Custom_Icons_Menu_Item;
use Modulix\Modules\AssetsManager\AssetTypes\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Editor_One_Icons_Menu_Item extends Custom_Icons_Menu_Item implements Menu_Item_Interface {

	public function get_position(): int {
		return 20;
	}

	public function get_slug(): string {
		return Icons_Manager::MENU_SLUG;
	}

	public function get_parent_slug(): string {
		return Menu_Config::ELEMENTOR_MENU_SLUG;
	}

	public function get_label(): string {
		return esc_html__( 'Icons', 'modulix' );
	}

	public function get_group_id(): string {
		return Menu_Config::CUSTOM_ELEMENTS_GROUP_ID;
	}
}

