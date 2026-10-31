<?php
namespace Modulix\Core\App\Modules\SiteEditor\Data\Responses;
// Modulix: derived from Elementor Pro 4.0.4, core/app/modules/site-editor/data/responses/lock-error-response.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Lock_Error_Response extends \WP_Error {
	public function __construct( $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		parent::__construct(
			'post_lock',
			sprintf(
				/* translators: %s: User display name. */
				esc_html__( '%s is currently editing this template, please try again later', 'modulix' ),
				$user->display_name
			),
			[
				'status' => 403,
				'locked_by_user_id' => $user_id,
				'locked_by_user_name' => $user->display_name,
			]
		);
	}
}
