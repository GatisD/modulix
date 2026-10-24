<?php
namespace Modulix\Modules\AssetsManager\AssetTypes\Icons\IconSets;
// Modulix: derived from Elementor Pro 4.0.4, modules/assets-manager/asset-types/icons/icon-sets/fontello.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Core\Utils;
use Modulix\Security\IconPackageName;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Fontello extends Icon_Set_Base {

	protected $data_file = 'config.json';
	protected $stylesheet_file = '';
	protected $allowed_zipped_files = [ 'config.json', 'demo.html', 'README.txt', 'LICENSE.txt', 'css/', 'font/' ];
	protected $allowed_webfont_extensions = [ 'woff', 'woff2', 'ttf', 'svg', 'otf' ];

	protected function prepare() {
		$name = IconPackageName::validate( (string) $this->get_name() );
		$this->remove_fontello_styling( $name );
		$this->dir_name = $this->get_unique_name( $name );
	}

	public function get_type() {
		return esc_html__( 'Fontello', 'modulix' );
	}

	public function is_valid() {
		if ( ! file_exists( $this->directory . $this->data_file ) ) {
			return false; // missing data file
		}
		return true;
	}

	private function remove_fontello_styling( $name ) {
		$filename = $this->directory . 'css/' . $name . '.css';
		$stylesheet = Utils::_unstable_file_get_contents( $filename );
		$stylesheet = str_replace( [ 'margin-left: .2em;', 'margin-right: .2em;' ], [ '', '' ], $stylesheet );
		file_put_contents( $filename, $stylesheet );
	}

	private function get_json() {
		return json_decode( Utils::_unstable_file_get_contents( $this->directory . $this->data_file ) );
	}

	protected function extract_icon_list() {
		$config = $this->get_json();
		if ( ! isset( $config->glyphs ) ) {
			return false; //  missing icons list
		}
		$icons = [];
		foreach ( $config->glyphs as $icon ) {
			$icons[] = $icon->css;
		}
		return $icons;
	}

	protected function get_prefix() {
		$config = $this->get_json();
		if ( ! isset( $config->css_prefix_text ) ) {
			return false; //  missing css_prefix_text
		}
		return $config->css_prefix_text;
	}

	public function get_name() {
		$config = $this->get_json();
		if ( ! isset( $config->name ) ) {
			return false; //  missing name
		}
		return (string) $config->name;
	}

	protected function get_stylesheet() {
		$name = $this->get_name();
		if ( ! $name ) {
			return false; //  missing name
		}
		return $this->get_url() . '/css/' . $name . '.css';
	}
}
