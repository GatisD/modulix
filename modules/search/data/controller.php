<?php
namespace Modulix\Modules\Search\Data;
// Modulix: derived from Elementor Pro 4.0.4, modules/search/data/controller.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\Search\Data\Endpoints\Base;
use Modulix\Core\Data\Controller as Controller_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Controller extends Controller_Base {
	public function get_name() {
		return 'search';
	}

	protected function register_endpoints() {
		$this->register_endpoint( Endpoints\Refresh_Search::class );
	}
}
