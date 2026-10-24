<?php

namespace Modulix\Modules\Forms\Submissions\EditorOneMenuItems;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/submissions/editor-one-menu-items/editor-one-submissions-menu-item.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Admin\EditorOneMenu\Interfaces\Menu_Item_Third_Level_Interface;
use Elementor\Modules\EditorOne\Classes\Menu_Config;
use Modulix\Modules\Forms\Submissions\AdminMenuItems\Submissions_Menu_Item;
use Modulix\Modules\Forms\Submissions\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Editor_One_Submissions_Menu_Item extends Submissions_Menu_Item implements Menu_Item_Third_Level_Interface {

	public function get_position(): int {
		return 50;
	}

	public function get_slug(): string {
		return Component::PAGE_ID;
	}

	public function get_parent_slug(): string {
		return Menu_Config::ELEMENTOR_HOME_MENU_SLUG;
	}

	public function get_label(): string {
		return esc_html__( 'Submissions', 'modulix' );
	}

	public function get_group_id(): string {
		return Menu_Config::EDITOR_GROUP_ID;
	}

	public function get_icon(): string {
		return 'send';
	}

	public function has_children(): bool {
		return false;
	}
}

