<?php

namespace Modulix\Modules\FloatingButtons;
// Modulix: derived from Elementor Pro 4.0.4, modules/floating-buttons/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): widgets only: Pro's floating-buttons document type, theme location and printing removed - without Pro, Elementor core prints include/general floating buttons itself; other conditions are not supported yet.

use Elementor\Core\Base\Module as BaseModule;
use Elementor\Plugin;
use Elementor\Utils as ElementorUtils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends BaseModule {

	const EXPERIMENT_NAME = 'floating-buttons';

	const FLOATING_BUTTONS_DOCUMENT_TYPE = 'floating-buttons';

	const CPT_FLOATING_BUTTONS = 'e-floating-buttons';

	public static function is_active(): bool {
		return class_exists( 'Elementor\Modules\FloatingButtons\Module' ) &&
		Plugin::$instance->experiments->is_feature_active( 'container' );
	}

	public function get_name(): string {
		return static::EXPERIMENT_NAME;
	}

	public function get_widgets(): array {
		$floating_buttons = [
			'Contact_Buttons_Var_1',
			'Contact_Buttons_Var_3',
			'Contact_Buttons_Var_4',
			'Contact_Buttons_Var_5',
			'Contact_Buttons_Var_6',
			'Contact_Buttons_Var_7',
			'Contact_Buttons_Var_8',
			'Contact_Buttons_Var_9',
			'Contact_Buttons_Var_10',
		];

		$floating_bars = [
			'Floating_Bars_Var_2',
			'Floating_Bars_Var_3',
		];

		return class_exists( 'Elementor\Modules\FloatingButtons\Base\Widget_Floating_Bars_Base' ) ? array_merge( $floating_buttons, $floating_bars ) : $floating_buttons;
	}

	public static function get_floating_elements_types() {
		return [
			'floating-buttons' => esc_html__( 'Floating Buttons', 'modulix' ),
			'floating-bars' => esc_html__( 'Floating Bars', 'modulix' ),
		];
	}

	public function __construct() {
		parent::__construct();
	}

}
