<?php
namespace Modulix\Modules\DisplayConditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/display-conditions-control.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Modules\AtomicWidgets\Base\Atomic_Control_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Display_Conditions_Control extends Atomic_Control_Base {
	private ?bool $disabled = null;

	public function get_type(): string {
		return 'display-conditions';
	}

	public function set_disabled( bool $disabled ): self {
		$this->disabled = $disabled;

		return $this;
	}

	public function get_props(): array {
		$props = [];

		if ( null !== $this->disabled ) {
			$props['disabled'] = $this->disabled;
		}

		return $props;
	}
}
