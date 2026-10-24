<?php
namespace Modulix\Modules\Forms\Submissions\Data\Responses;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/submissions/data/responses/query-failed-response.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Query_Failed_Response  extends \WP_Error {
	public function __construct( $query_error_message, $message = null ) {
		if ( ! $message ) {
			$message = esc_html__( 'Could not retrieve query data.', 'modulix' );
		}

		$this->log_error( $query_error_message );

		parent::__construct(
			'rest_internal_error',
			$message,
			[ 'status' => 500 ]
		);
	}

	private function log_error( $query_error_message ) {
		Plugin::elementor()->logger->error( $query_error_message );
	}
}
