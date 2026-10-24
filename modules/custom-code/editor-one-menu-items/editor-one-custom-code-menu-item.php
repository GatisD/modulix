<?php

namespace Modulix\Modules\CustomCode\EditorOneMenuItems;
// Modulix: derived from Elementor Pro 4.0.4, modules/custom-code/editor-one-menu-items/editor-one-custom-code-menu-item.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Admin\EditorOneMenu\Interfaces\Menu_Item_Interface;
use Elementor\Modules\EditorOne\Classes\Menu_Config;
use Modulix\Modules\CustomCode\AdminMenuItems\Custom_Code_Menu_Item;
use Modulix\Modules\CustomCode\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Editor_One_Custom_Code_Menu_Item extends Custom_Code_Menu_Item implements Menu_Item_Interface {

	public function get_position(): int {
		return 30;
	}

	public function get_slug(): string {
		return Module::MENU_SLUG;
	}

	public function get_parent_slug(): string {
		return Menu_Config::ELEMENTOR_MENU_SLUG;
	}

	public function get_label(): string {
		return esc_html__( 'Code', 'modulix' );
	}

	public function get_group_id(): string {
		return Menu_Config::CUSTOM_ELEMENTS_GROUP_ID;
	}
}

