<?php
namespace Modulix\Modules\DynamicTags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): licence checks, ACF/Toolset/Pods, author meta filter and WooCommerce redirect removed; tags limited to Site_Logo and Site_URL.

use Elementor\Modules\DynamicTags\Module as TagsModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends TagsModule {

	const AUTHOR_GROUP = 'author';

	const POST_GROUP = 'post';

	const COMMENTS_GROUP = 'comments';

	const SITE_GROUP = 'site';

	const ARCHIVE_GROUP = 'archive';

	const MEDIA_GROUP = 'media';

	const ACTION_GROUP = 'action';

	const WOOCOMMERCE_GROUP = 'woocommerce';

	public function get_name() {
		return 'tags';
	}

	public function get_tag_classes_names() {
		return [
			'Site_Logo',
			'Site_URL',
		];
	}

	public function get_groups() {
		return [
			self::POST_GROUP => [
				'title' => esc_html__( 'Post', 'modulix' ),
			],
			self::ARCHIVE_GROUP => [
				'title' => esc_html__( 'Archive', 'modulix' ),
			],
			self::SITE_GROUP => [
				'title' => esc_html__( 'Site', 'modulix' ),
			],
			self::MEDIA_GROUP => [
				'title' => esc_html__( 'Media', 'modulix' ),
			],
			self::ACTION_GROUP => [
				'title' => esc_html__( 'Actions', 'modulix' ),
			],
			self::AUTHOR_GROUP => [
				'title' => esc_html__( 'Author', 'modulix' ),
			],
			self::COMMENTS_GROUP => [
				'title' => esc_html__( 'Comments', 'modulix' ),
			],
			self::WOOCOMMERCE_GROUP => [
				'title' => esc_html__( 'WooCommerce', 'modulix' ),
			],
		];
	}
}
