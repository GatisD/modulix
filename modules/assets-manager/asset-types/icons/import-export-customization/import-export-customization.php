<?php

namespace Modulix\Modules\AssetsManager\AssetTypes\Icons\ImportExportCustomization;
// Modulix: derived from Elementor Pro 4.0.4, modules/assets-manager/asset-types/icons/import-export-customization/import-export-customization.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\App\Modules\ImportExportCustomization\Processes\Export;
use Elementor\App\Modules\ImportExportCustomization\Processes\Import;
use Elementor\App\Modules\ImportExportCustomization\Processes\Revert;

class Import_Export_Customization {
	const FILE_NAME = 'custom-icons';

	public function register_hooks() {
		add_action( 'elementor/import-export-customization/export-kit', function ( Export $export ) {
			$export->register( new Export_Runner() );
		} );

		add_action( 'elementor/import-export-customization/import-kit', function ( Import $import ) {
			$import->register( new Import_Runner() );
		} );

		add_action( 'elementor/import-export-customization/revert-kit', function ( Revert $revert ) {
			$revert->register( new Revert_Runner() );
		} );
	}
}
