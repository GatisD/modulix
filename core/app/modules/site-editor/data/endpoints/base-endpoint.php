<?php
namespace Modulix\Core\App\Modules\SiteEditor\Data\Endpoints;
// Modulix: derived from Elementor Pro 4.0.4, core/app/modules/site-editor/data/endpoints/base-endpoint.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Data\Base\Endpoint;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

abstract class Base_Endpoint extends Endpoint {
	/**
	 * Check if post is lock.
	 *
	 * @param $post_id
	 *
	 * @return bool|false|int
	 */
	protected function is_post_lock( $post_id ) {
		require_once ABSPATH . 'wp-admin/includes/post.php';

		return wp_check_post_lock( $post_id );
	}
}
