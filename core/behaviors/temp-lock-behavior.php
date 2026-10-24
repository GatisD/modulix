<?php
namespace Modulix\Core\Behaviors;
// Modulix: derived from Elementor Pro 4.0.4, core/behaviors/temp-lock-behavior.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// TODO: Used here for testing. Should be removed when it'll be available in the Core.
interface Temp_Lock_Behavior {

	/**
	 * @return bool
	 */
	public function is_locked();

	/**
	 * @return array {
	 *
	 *    @type bool $is_locked
	 *
	 *    @type array $badge {
	 *         @type string $icon
	 *         @type string $text
	 *     }
	 *
	 *    @type array $content {
	 *         @type string $heading
	 *         @type string $description
	 *   }
	 *
	 *    @type array $button {
	 *         @type string $text
	 *         @type string $url
	 *   }
	 *
	 * }
	 */
	public function get_config();
}
