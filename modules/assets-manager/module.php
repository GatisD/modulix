<?php
namespace Modulix\Modules\AssetsManager;
// Modulix: derived from Elementor Pro 4.0.4, modules/assets-manager/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\AssetsManagerAdmin;
use Modulix\Base\Module_Base;
use Modulix\Modules\AssetsManager\AssetTypes\Fonts\ImportExport\Import_Export as Fonts_Import_Export;
use Modulix\Modules\AssetsManager\AssetTypes\Fonts\ImportExportCustomization\Import_Export_Customization as Fonts_Import_Export_Customization;
use Modulix\Modules\AssetsManager\AssetTypes\Icons\ImportExport\Import_Export as Icons_Import_Export;
use Modulix\Modules\AssetsManager\AssetTypes\Icons\ImportExportCustomization\Import_Export_Customization as Icons_Import_Export_Customization;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {

	private $asset_managers = [];

	public function get_name() {
		return 'assets-manager';
	}

	public function add_asset_manager( $name, $instance ) {
		$this->asset_managers[ $name ] = $instance;
	}

	public function get_assets_manager( $id = null ) {
		if ( $id ) {
			if ( ! isset( $this->asset_managers[ $id ] ) ) {
				return null;
			}

			return $this->asset_managers[ $id ];
		}

		return $this->asset_managers;
	}

	public function __construct() {
		parent::__construct();

		$this->add_asset_manager( 'font', new AssetTypes\Fonts_Manager() );
		$this->add_asset_manager( 'icon', new AssetTypes\Icons_Manager() );
		add_action( 'admin_enqueue_scripts', [ AssetsManagerAdmin::class, 'enqueue' ] );

		$this->register_import_export();
	}

	private function register_import_export() {
		( new Fonts_Import_Export() )->register_hooks();
		( new Fonts_Import_Export_Customization() )->register_hooks();
		( new Icons_Import_Export() )->register_hooks();
		( new Icons_Import_Export_Customization() )->register_hooks();
	}
}
