<?php
namespace Modulix\Modules\Woocommerce\ImportExportCustomization;
// Modulix: derived from Elementor Pro 4.0.4, modules/woocommerce/import-export-customization/woocommerce-settings-revert.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\App\Modules\ImportExportCustomization\Runners\Revert\Revert_Runner_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woocommerce_Settings_Revert extends Revert_Runner_Base {

	public static function get_name(): string {
		return 'woocommerce-settings';
	}

	public function should_revert( array $data ): bool {
		return isset( $data['runners'][ static::get_name() ] );
	}

	public function revert( array $data ) {
		$runner_data = $data['runners'][ static::get_name() ];

		$previous_pages = $runner_data['previous_pages'] ?? [];

		foreach ( $previous_pages as $key => $value ) {
			update_option( $key, $value );
		}
	}
}

