<?php
namespace Modulix\Modules\Forms\Actions;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/actions/cf7db.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\Forms\Classes\Action_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class CF7DB extends Action_Base {

	public function get_name() {
		return 'cf7db';
	}

	public function get_label() {
		return 'Contact Form to Database';
	}

	public function register_settings_section( $widget ) {}

	public function on_export( $element ) {}

	public function run( $record, $ajax_handler ) {
		$data = (object) [
			'title' => $record->get_form_settings( 'form_name' ),
			'posted_data' => $record->get_formatted_data( true ),
		];

		// Call hook to submit data
		do_action_ref_array( 'cfdb_submit', [ $data ] );
	}
}
