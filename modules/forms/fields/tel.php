<?php
namespace Modulix\Modules\Forms\Fields;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/fields/tel.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\Forms\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Tel extends Field_Base {

	public function get_type() {
		return 'tel';
	}

	public function get_name() {
		return esc_html__( 'Tel', 'modulix' );
	}

	public function render( $item, $item_index, $form ) {
		$form->add_render_attribute( 'input' . $item_index, 'class', 'elementor-field-textual' );
		$form->add_render_attribute( 'input' . $item_index, 'pattern', '[0-9()#&+*-=.]+' );
		$form->add_render_attribute( 'input' . $item_index, 'title', esc_html__( 'Only numbers and phone characters (#, -, *, etc) are accepted.', 'modulix' ) );
		?>
		<input size="1" <?php $form->print_render_attribute_string( 'input' . $item_index ); ?>>

		<?php
	}

	public function validation( $field, Classes\Form_Record $record, Classes\Ajax_Handler $ajax_handler ) {
		if ( empty( $field['value'] ) ) {
			return;
		}
		if ( preg_match( '/^[0-9()#&+*-=.]+$/', $field['value'] ) !== 1 ) {
			$ajax_handler->add_error( $field['id'], esc_html__( 'The field accepts only numbers and phone characters (#, -, *, etc).', 'modulix' ) );
		}
	}
}
