<?php
namespace Modulix\Modules\QueryControl;
// Modulix: constant names and values from Elementor Pro 4.0.4 modules/query-control/module.php, GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): constants only; the query control itself is not lifted.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/** Lifted conditions read these constants; their editor controls are not served in phase 1. */
final class Module {
	const QUERY_CONTROL_ID = 'query';
	const QUERY_OBJECT_POST = 'post';
	const QUERY_OBJECT_TAX = 'tax';
	const QUERY_OBJECT_AUTHOR = 'author';
	const QUERY_OBJECT_JS = 'js';
}
