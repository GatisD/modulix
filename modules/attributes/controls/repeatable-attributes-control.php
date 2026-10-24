<?php
namespace Modulix\Modules\Attributes\Controls;
// Modulix: derived from Elementor Pro 4.0.4, modules/attributes/controls/repeatable-attributes-control.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Modules\AtomicWidgets\Controls\Types\Repeatable_Control as Core_Repeatable_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Repeatable_Attributes_Control extends Core_Repeatable_Control {
	private ?array $add_item_tooltip_props = null;

	public function set_addItemTooltipProps( array $props ): self {
		$this->add_item_tooltip_props = $props;

		return $this;
	}

	public function get_props(): array {
		$props = parent::get_props();

		if ( null !== $this->add_item_tooltip_props ) {
			$props['addItemTooltipProps'] = (object) $this->add_item_tooltip_props;
		}

		return $props;
	}
}


