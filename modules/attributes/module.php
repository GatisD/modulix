<?php
namespace Modulix\Modules\Attributes;
// Modulix: derived from Elementor Pro 4.0.4, modules/attributes/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Base\Module_Base;
use Modulix\Modules\Attributes\Controls\Repeatable_Attributes_Control;
use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Modulix\Modules\Attributes\PropsResolver\Transformers\Settings\Pro_Attributes_Transformer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {

	public function get_name() {
		return 'attributes';
	}

	public function __construct() {
		parent::__construct();

		add_action(
			'elementor/atomic-widgets/settings/transformers/register',
			function ( $transformers ) {
				$attributes_key = Attributes_Prop_Type::get_key();
				$transformers->register(
					$attributes_key,
					new Pro_Attributes_Transformer()
				);
			},
			20
		);

		add_filter(
			'elementor/atomic-widgets/controls',
			fn( $element_controls, $atomic_element ) => $this->inject_attrs_control( $element_controls, $atomic_element ),
			10,
			2
		);
	}

	private function inject_attrs_control( $element_controls, $atomic_element ) {
		$schema = $atomic_element::get_props_schema();

		if ( ! array_key_exists( 'attributes', $schema ) ) {
			return $element_controls;
		}

		foreach ( $element_controls as $item ) {
			if ( $item instanceof Section && $item->get_id() === 'settings' ) {
				$is_empty_controls = empty( $item->get_items() );

				$control = Repeatable_Attributes_Control::bind_to( 'attributes' )
					->set_meta( [ 'topDivider' => ! $is_empty_controls ] )
					->set_repeaterLabel( __( 'Attributes', 'modulix' ) )
					->set_initialValues(
						[
							'key'   => [
								'$$type' => 'string',
								'value'  => '',
							],
							'value' => [
								'$$type' => 'string',
								'value'  => '',
							],
						]
					)
					->set_child_control_props( (object) [] )
					->set_patternLabel( '${value.key.value}="${value.value.value}"' )
					->set_placeholder( 'Empty attribute' )
					->set_child_control_type( 'attributes' )
					->hide_duplicate()
					->hide_toggle();

				if ( method_exists( $control, 'set_prop_key' ) ) {
					$control->set_prop_key( 'attributes' );
				}

				$item->add_item( $control );
				break;
			}
		}

		return $element_controls;
	}
}
