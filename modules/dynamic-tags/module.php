<?php
namespace Modulix\Modules\DynamicTags;
// Modulix: derived from Elementor Pro 4.0.4, modules/dynamic-tags/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.
// Modified by Modulix (2026-10-23): licence checks, ACF/Toolset/Pods, author meta filter and WooCommerce redirect removed; tags limited to Site_Logo and Site_URL.
// Modified by Modulix (2026-10-23, plan 6a): Contact_URL added (builds links from tag settings only; fleet uses it in icon lists).
// Modified by Modulix (2026-10-23, plan 5): post, archive, site, author, comments and date tags, Post_Custom_Field, the author meta save filter and the ACF component (active with ACF, no licence gate) restored. Toolset and Pods remain removed.
// Modified by Modulix (2026-10-24): internal URL, action, request, shortcode and current-user tags restored with output and access boundaries.

use Elementor\Modules\DynamicTags\Module as TagsModule;
use Modulix\Modules\DynamicTags\ACF;

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

	public function __construct() {
		parent::__construct();

		$this->add_component( 'author-meta-filter', new Components\Author_Meta_Filter() );

		// ACF 5 and up
		if ( class_exists( '\acf' ) && function_exists( 'acf_get_field_groups' ) ) {
			$this->add_component( 'acf', new ACF\Module() );
		}

		add_filter( 'elementor/document/save/data', [ $this->get_component( 'author-meta-filter' ), 'filter' ], 10, 2 );
	}

	public function get_name() {
		return 'tags';
	}

	public function get_tag_classes_names() {
		return [
			'Archive_Description',
			'Archive_Meta',
			'Archive_Title',
			'Archive_URL',
			'Author_Info',
			'Author_Meta',
			'Author_Name',
			'Author_Profile_Picture',
			'Author_URL',
			'Comments_Number',
			'Comments_URL',
			'Page_Title',
			'Post_Date',
			'Post_Excerpt',
			'Post_Featured_Image',
			'Post_Gallery',
			'Post_ID',
			'Post_Terms',
			'Post_Time',
			'Post_Title',
			'Post_URL',
			'Site_Logo',
			'Site_Tagline',
			'Site_Title',
			'Site_URL',
			'Internal_URL',
			'Current_Date_Time',
			'Reload_Page',
			'Request_Parameter',
			'Lightbox',
			'Featured_Image_Data',
			'Shortcode',
			'Post_Custom_Field',
			'Contact_URL',
			'User_Info',
			'User_Profile_Picture',
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

	// TODO: Remove this in 3.37.0
	public static function add_v4_svg_category( $categories ) {
		return defined( 'Elementor\Modules\DynamicTags\Module::SVG_CATEGORY' )
			? array_merge( $categories, [ \Elementor\Modules\DynamicTags\Module::SVG_CATEGORY ] )
			: $categories;
	}
}
