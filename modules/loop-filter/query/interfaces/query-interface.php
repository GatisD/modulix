<?php

namespace Modulix\Modules\LoopFilter\Query\Interfaces;
// Modulix: derived from Elementor Pro 4.0.4, modules/loop-filter/query/interfaces/query-interface.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Modulix\Modules\LoopFilter\Query\Taxonomy_Manager;

interface Query_Interface {
	public function __construct( $filter_terms, Taxonomy_Manager $taxonomy_manager );
	public function get_query();
}
