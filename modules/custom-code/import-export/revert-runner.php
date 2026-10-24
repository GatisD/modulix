<?php

namespace Modulix\Modules\CustomCode\ImportExport;
// Modulix: derived from Elementor Pro 4.0.4, modules/custom-code/import-export/revert-runner.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\App\Modules\ImportExport\Runners\Revert\Revert_Runner_Base;
use Modulix\Modules\CustomCode\Module as Custom_Code_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Revert_Runner extends Revert_Runner_Base {

	public static function get_name(): string {
		return 'custom-code';
	}

	public function should_revert( array $data ): bool {
		return (
			isset( $data['runners'] ) &&
			array_key_exists( static::get_name(), $data['runners'] )
		);
	}

	public function revert( array $data ) {
		$metadata = $data['runners'][ static::get_name() ] ?? [];

		$this->revert_imported_snippets( $metadata );
	}

	private function revert_imported_snippets( $metadata ) {
		$imported_snippets = $metadata['imported_snippets'] ?? [];

		foreach ( $imported_snippets as $snippet ) {
			wp_delete_post( $snippet['id'], true );
		}
	}
}
