<?php

namespace Modulix\Modules\CustomCode\ImportExport;
// Modulix: derived from Elementor Pro 4.0.4, modules/custom-code/import-export/import-export.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\App\Modules\ImportExport\Processes\Export;
use Elementor\App\Modules\ImportExport\Processes\Import;
use Elementor\App\Modules\ImportExport\Processes\Revert;

class Import_Export {
	const FILE_NAME = 'custom-code';

	public function register_hooks() {
		add_action( 'elementor/import-export/export-kit', function ( Export $export ) {
			$export->register( new Export_Runner() );
		} );

		add_action( 'elementor/import-export/import-kit', function ( Import $import ) {
			$import->register( new Import_Runner() );
		} );

		add_action( 'elementor/import-export/revert-kit', function ( Revert $revert ) {
			$revert->register( new Revert_Runner() );
		} );
	}
}
