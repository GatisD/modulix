<?php

namespace Modulix\Modules\Popup\EditorOneMenuItems;
// Modulix: derived from Elementor Pro 4.0.4, modules/popup/editor-one-menu-items/editor-one-popups-menu-item.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Admin\EditorOneMenu\Interfaces\Menu_Item_Interface;
use Elementor\Modules\EditorOne\Classes\Menu_Config;
use Modulix\Modules\Popup\AdminMenuItems\Popups_Menu_Item;
use Modulix\Modules\Popup\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Editor_One_Popups_Menu_Item extends Popups_Menu_Item implements Menu_Item_Interface {

	public function get_position(): int {
		return 70;
	}

	public function get_slug(): string {
		return Module::instance()->get_admin_url( true );
	}

	public function get_parent_slug(): string {
		return Menu_Config::ELEMENTOR_MENU_SLUG;
	}

	public function get_label(): string {
		return esc_html__( 'Popups', 'modulix' );
	}

	public function get_group_id(): string {
		return Menu_Config::TEMPLATES_GROUP_ID;
	}
}

