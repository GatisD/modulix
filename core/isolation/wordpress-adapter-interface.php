<?php

namespace Modulix\Core\Isolation;
// Modulix: derived from Elementor Pro 4.0.4, core/isolation/wordpress-adapter-interface.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

interface Wordpress_Adapter_Interface {
	public function has_post_thumbnail();
	public function get_comments_number();
	public function is_author( $author = ''): bool;
	public function wp_get_attachment_caption( $attachment_id ): string;
	public function get_the_title( $post_id ): string;
	public function current_user_can( $capability, ...$args ): bool;
}
