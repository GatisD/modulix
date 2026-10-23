<?php
namespace Modulix\Modules\ThemeBuilder\Classes;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/classes/templates-types-manager.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): only the header and footer document types in phase 1.

use Modulix\Modules\ThemeBuilder\Documents;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Templates_Types_Manager {
	private $docs_types = [];

	public function __construct() {
		add_action( 'elementor/documents/register', [ $this, 'register_documents' ] );
	}

	public function get_types_config( $args = [] ) {
		$config = [];

		$document_types = Plugin::elementor()->documents->get_document_types( $args );

		foreach ( $document_types as $type => $document_type ) {
			$properties = $document_type::get_properties();

			if ( ( new $document_type() ) instanceof Documents\Theme_Document ) {
				$config[ $type ] = $properties;
			}
		}

		return $config;
	}

	public function register_documents() {
		$this->docs_types = [
			'header' => Documents\Header::get_class_full_name(),
			'footer' => Documents\Footer::get_class_full_name(),
		];

		foreach ( $this->docs_types as $type => $class_name ) {
			Plugin::elementor()->documents->register_document_type( $type, $class_name );
		}
	}
}
