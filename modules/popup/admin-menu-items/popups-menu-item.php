<?php

namespace Modulix\Modules\Popup\AdminMenuItems;
// Modulix: derived from Elementor Pro 4.0.4, modules/popup/admin-menu-items/popups-menu-item.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Admin\Menu\Interfaces\Admin_Menu_Item;
use Elementor\TemplateLibrary\Source_Local;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Popups_Menu_Item implements Admin_Menu_Item {
	public function get_capability(): string {
		return 'publish_posts';
	}

	public function get_label(): string {
		return esc_html__( 'Popups', 'modulix' );
	}

	public function get_parent_slug(): string {
		return Source_Local::ADMIN_MENU_SLUG;
	}

	public function get_position(): ?int {
		return null;
	}

	public function is_visible(): bool {
		return true;
	}
}
