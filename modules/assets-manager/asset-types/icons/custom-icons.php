<?php
namespace Modulix\Modules\AssetsManager\AssetTypes\Icons;
// Modulix: derived from Elementor Pro 4.0.4, modules/assets-manager/asset-types/icons/custom-icons.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Utils\Exceptions;
use Modulix\Core\Utils;
use Modulix\Modules\AssetsManager\Classes\Assets_Base;
use Modulix\Modules\AssetsManager\AssetTypes\Icons_Manager;
use Elementor\Core\Common\Modules\Ajax\Module as Ajax;
use Modulix\Plugin;
use Modulix\Security\IconArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Custom_Icons extends  Assets_Base {

	const META_KEY = 'elementor_custom_icon_set_config';
	const OPTION_NAME = 'elementor_custom_icon_sets_config';

	public $current_post_id = 0;

	public function get_name() {
		return esc_html__( 'Custom Icons', 'modulix' );
	}

	public function get_type() {
		return 'custom-icons';
	}

	public function add_meta_box() {
		add_meta_box(
			'elementor-custom-icons-metabox',
			__( 'Icon Set', 'modulix' ),
			[ $this, 'render_metabox' ],
			Icons_Manager::CPT,
			'normal',
			'default'
		);
	}

	public static function get_icon_set_config( $id ) {
		return get_post_meta( $id, self::META_KEY, true );
	}

	public function render_metabox( $post ) {
		wp_enqueue_media();

		$save_data = self::get_icon_set_config( $post->ID );

		$fields = [
			[
				'id' => 'open_div',
				'field_type' => 'html_tag',
				'label' => false,
				'tag' => 'div',
				'attributes' => [
					'class' => 'elementor-custom-icons-metabox',
				],
			],
			[
				'id' => 'zip_upload',
				'field_type' => 'dropzone',
				'accept' => 'zip,application/octet-stream,application/zip,application/x-zip,application/x-zip-compressed',
				'label' => false,
				'sub-label' => esc_html__( 'Your Fontello, IcoMoon or Fontastic .zip file', 'modulix' ),
			],
			[
				'id' => 'close_div',
				'field_type' => 'html_tag',
				'label' => false,
				'tag' => 'div',
				'close' => true,
			],
			[
				'id' => self::META_KEY,
				'name' => self::META_KEY,
				'field_type' => 'input',
				'input_type' => 'hidden',
				'label' => false,
				'value' => $save_data,
				'saved' => $save_data,
			],
			[
				'id' => Icons_Manager::CPT . '_nonce',
				'name' => Icons_Manager::CPT . '_nonce',
				'field_type' => 'input',
				'input_type' => 'hidden',
				'label' => false,
				'value' => wp_create_nonce( Icons_Manager::CPT ),
			],
		];

		foreach ( $fields as $field ) {
			$field['saved'] = isset( $field['saved'] ) ? $field['saved'] : '';
		}

		$this->print_metabox( $fields );
	}

	public function save_post_meta( $post_id, $post, $update ) {
		// If this is an autosave, our form has not been submitted,
		// so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Check if our nonce is set.
		if ( ! isset( $_POST[ Icons_Manager::CPT . '_nonce' ] ) ) {
			return $post_id;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce(
			Utils::_unstable_get_super_global_value( $_POST, Icons_Manager::CPT . '_nonce' ),
			Icons_Manager::CPT
		) ) {
			return $post_id;
		}

		if ( ! isset( $_POST[ self::META_KEY ] ) ) {
			return delete_post_meta( $post_id, self::META_KEY );
		}

		// PHPCS - It will be sanitized in the next line.
		$json = json_decode( stripslashes_deep( $_POST[ self::META_KEY ] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! is_array( $json ) ) {
			return $post_id;
		}

		$this->persist_icon_set_config( (int) $post_id, $json );
	}

	private function persist_icon_set_config( int $post_id, array $config ): bool {
		if ( ! array_key_exists( 'prefix', $config ) || ! is_string( $config['prefix'] ) ) {
			return false;
		}

		$config = $this->sanitize_text_field_recursive( $config );
		$encoded = json_encode( $config );
		if ( false === $encoded ) {
			return false;
		}

		update_post_meta( $post_id, self::META_KEY, $encoded );
		$stored = json_decode( (string) get_post_meta( $post_id, self::META_KEY, true ), true );
		if ( $config !== $stored ) {
			return false;
		}

		self::clear_icon_list_option();
		return true;
	}

	public static function get_supported_icon_sets() {
		$icon_sets = [
			'fontastic' => __NAMESPACE__ . '\IconSets\Fontastic',
			'fontello' => __NAMESPACE__ . '\IconSets\Fontello',
			'icomoon' => __NAMESPACE__ . '\IconSets\Icomoon',
		];

		$additional_icon_sets = [];

		/**
		 * Additional icon sets.
		 *
		 * Filters the icon types supported by Elementor Pro.
		 *
		 * By default Elementor Pro supports 'fontastic', 'fontello' and 'icomoon'.
		 * This hook allows developers to add additional icon sets.
		 *
		 * @param array $additional_icon_sets Additional icon sets.
		 */
		$additional_icon_sets = apply_filters( 'elementor_pro/icons_manager/custom_icons/additional_supported_types', $additional_icon_sets );

		return array_merge( $additional_icon_sets, $icon_sets );
	}

	private function get_active_icon_sets() {
		$icons = new \WP_Query( [
			'post_type' => Icons_Manager::CPT,
			'posts_per_page' => -1,
		] );
		$custom_icon_sets = [];
		foreach ( $icons->posts as $icon_set ) {
			$set_config = json_decode( self::get_icon_set_config( $icon_set->ID ), true );
			$set_config['custom_icon_post_id'] = $icon_set->ID;
			$set_config['label'] = $icon_set->post_title;
			$custom_icon_sets[ $set_config['name'] ] = $set_config;
		}
		return $custom_icon_sets;
	}

	/**
	 * get_wp_filesystem
	 * @return \WP_Filesystem_Base
	 */
	public static function get_wp_filesystem() {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem;
	}

	private function upload() {
		$file = Utils::_unstable_get_super_global_value( $_FILES, 'zip_upload' );
		$filename = $file['name'];
		$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		if ( 'zip' !== $ext ) {
			$this->cleanup_uploaded_file( $file );
			return new \WP_Error( 'unsupported_file', esc_html__( 'Only zip files are allowed', 'modulix' ) );
		}
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		// Handler upload archive file.
		$upload_result = wp_handle_upload( $file, [ 'test_form' => false ] );
		if ( isset( $upload_result['error'] ) ) {
			$this->cleanup_uploaded_file( $file );
			return new \WP_Error( 'upload_error', $upload_result['error'] );
		}
		return $upload_result['file'];
	}

	private function cleanup_uploaded_file( $file ) {
		if ( isset( $file['tmp_name'] ) && is_string( $file['tmp_name'] ) && is_uploaded_file( $file['tmp_name'] ) ) {
			@unlink( $file['tmp_name'] );
		}
	}

	private function extract_zip( $file, $to ) {
		if ( ! is_file( $file ) || filesize( $file ) > IconArchive::MAX_ARCHIVE_BYTES ) {
			@unlink( $file );
			return new \WP_Error( 'archive_too_large', esc_html__( 'The archive is too large.', 'modulix' ) );
		}

		$zip = new \ZipArchive();
		if ( true !== $zip->open( $file ) ) {
			@unlink( $file );
			return new \WP_Error( 'invalid_archive', esc_html__( 'Could not open the archive.', 'modulix' ) );
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( $zip->numFiles < 1 || $zip->numFiles > IconArchive::MAX_ENTRIES ) {
			$zip->close();
			@unlink( $file );
			return new \WP_Error( 'invalid_archive', esc_html__( 'The archive has an invalid number of entries.', 'modulix' ) );
		}

		$entries = [];
		$seen = [];
		$total_size = 0;
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = $zip->getNameIndex( $i );
			if ( ! is_string( $name ) || str_starts_with( $name, '__MACOSX/' ) ) {
				continue;
			}
			$stat = $zip->statIndex( $i );
			$attributes = 0;
			$operating_system = 0;
			$zip->getExternalAttributesIndex( $i, $operating_system, $attributes );
			$mode = ( $attributes >> 16 ) & 0170000;
			$size = is_array( $stat ) ? (int) ( $stat['size'] ?? -1 ) : -1;
			$compressed_size = is_array( $stat ) ? (int) ( $stat['comp_size'] ?? -1 ) : -1;

			if ( isset( $seen[ $name ] ) || ! IconArchive::entryAllowed( $name, $size, $compressed_size, $mode ) ) {
				$zip->close();
				@unlink( $file );
				return new \WP_Error( 'invalid_archive_entry', esc_html__( 'The archive contains an invalid entry.', 'modulix' ) );
			}
			$seen[ $name ] = true;
			$total_size += $size;
			if ( $total_size > IconArchive::MAX_EXPANDED_BYTES ) {
				$zip->close();
				@unlink( $file );
				return new \WP_Error( 'archive_too_large', esc_html__( 'The expanded archive is too large.', 'modulix' ) );
			}
			if ( ! IconArchive::entryNameAllowed( $name ) ) {
				continue;
			}
			$entries[] = [ 'name' => $name, 'size' => $size ];
		}

		if ( file_exists( $to ) || is_link( $to ) || ! mkdir( $to, 0700, true ) ) {
			$zip->close();
			@unlink( $file );
			return new \WP_Error( 'archive_write_error', esc_html__( 'Could not prepare the archive directory.', 'modulix' ) );
		}
		$base = realpath( $to );
		if ( false === $base ) {
			$zip->close();
			@unlink( $file );
			return new \WP_Error( 'archive_write_error', esc_html__( 'Could not prepare the archive directory.', 'modulix' ) );
		}

		foreach ( $entries as $entry ) {
			$name = $entry['name'];
			$target = $base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, rtrim( $name, '/' ) );
			if ( str_ends_with( $name, '/' ) ) {
				wp_mkdir_p( $target );
				continue;
			}

			wp_mkdir_p( dirname( $target ) );
			$parent = realpath( dirname( $target ) );
			if ( false === $parent || ( $parent !== $base && ! str_starts_with( $parent, $base . DIRECTORY_SEPARATOR ) ) ) {
				return $this->archive_write_error( $zip, $file, $to );
			}

			$source = $zip->getStream( $name );
			$destination = @fopen( $target, 'xb' );
			if ( false === $source || false === $destination ) {
				if ( is_resource( $source ) ) {
					fclose( $source );
				}
				if ( is_resource( $destination ) ) {
					fclose( $destination );
				}
				return $this->archive_write_error( $zip, $file, $to );
			}
			$written = stream_copy_to_stream( $source, $destination, IconArchive::MAX_FILE_BYTES + 1 );
			fclose( $source );
			fclose( $destination );
			if ( $written !== $entry['size'] ) {
				return $this->archive_write_error( $zip, $file, $to );
			}
			@chmod( $target, 0644 );
		}

		$zip->close();
		@unlink( $file );
		return true;
	}

	private function archive_write_error( $zip, $file, $to ) {
		$zip->close();
		@unlink( $file );
		self::get_wp_filesystem()->rmdir( $to, true );
		return new \WP_Error( 'archive_write_error', esc_html__( 'Could not extract the archive.', 'modulix' ) );
	}

	protected function upload_and_extract_zip() {
		$zip_file = $this->upload();
		if ( is_wp_error( $zip_file ) ) {
			return $zip_file;
		}
		$filesystem = self::get_wp_filesystem();
		$extract_to = trailingslashit( get_temp_dir() ) . 'modulix-icons-' . wp_generate_uuid4();

		$unzipped = $this->extract_zip( $zip_file, $extract_to );
		if ( is_wp_error( $unzipped ) ) {
			return $unzipped;
		}

		// Find the right folder.
		$source_files = array_keys( $filesystem->dirlist( $extract_to ) );
		if ( count( $source_files ) === 0 ) {
			return new \WP_Error( 'incompatible_archive', esc_html__( 'Incompatible archive', 'modulix' ) );
		}

		if ( 1 === count( $source_files ) && $filesystem->is_dir( $extract_to . $source_files[0] ) ) {
			$directory = $extract_to . trailingslashit( $source_files[0] );
		} else {
			$directory = $extract_to;
		}
		return [
			'directory' => $directory,
			'extracted_to' => $extract_to,
		];
	}

	public function custom_icons_upload_handler( $data ) {
		if ( ! current_user_can( Icons_Manager::CAPABILITY ) ) {
			return new \WP_Error( Exceptions::FORBIDDEN, 'Access denied.' );
		}

		$this->current_post_id = $data['post_id'];
		$results = $this->upload_and_extract_zip();
		if ( is_wp_error( $results ) ) {
			return $results;
		}
		$supported_icon_sets = self::get_supported_icon_sets();
		foreach ( $supported_icon_sets as $key => $handler ) {
			/**
			 * @var IconSets\Icon_Set_Base $icon_set_handler
			 */
			$icon_set_handler = new $handler( $results['directory'] );

			if ( ! $icon_set_handler ) {
				continue;
			}
			if ( ! $icon_set_handler->is_valid() ) {
				continue;
			}
			try {
				$icon_set_handler->handle_new_icon_set();
			} catch ( \InvalidArgumentException $exception ) {
				continue;
			}
			$icon_set_handler->move_files( $this->current_post_id );
			$config = $icon_set_handler->build_config();

			// Notify about duplicate prefix
			if ( self::icon_set_prefix_exists( $config['prefix'] ) ) {
				$config['duplicate_prefix'] = true;
			}
			return [
				'config' => $config,
			];
		}
		return new \WP_Error( 'unsupported_zip_format', esc_html__( 'The zip file provided is not supported!', 'modulix' ) );
	}

	public function handle_delete_icon_set( $post_id ) {
		if ( Icons_Manager::CPT !== get_post_type( $post_id ) ) {
			return;
		}

		// remove all assets related to this icon set
		$attachments = get_attached_media( '', $post_id );

		foreach ( $attachments as $attachment ) {
			wp_delete_attachment( $attachment->ID, 'true' );
		}

		// remove icon set assets directory
		$icon_set_dir = get_post_meta( $post_id, '_elementor_icon_set_path', true );
		if ( ! empty( $icon_set_dir ) && is_dir( $icon_set_dir ) ) {
			$this::get_wp_filesystem()->rmdir( $icon_set_dir, true );
		}

		// Force refresh of list in Options Table
		self::clear_icon_list_option();
	}

	public static function clear_icon_list_option() {
		delete_option( self::OPTION_NAME );
	}

	public function display_post_states( $post_states, $post ) {
		if ( 'publish' !== $post->post_status || Icons_Manager::CPT !== $post->post_type ) {
			return $post_states;
		}

		$data = json_decode( self::get_icon_set_config( $post->ID ) );
		if ( ! empty( $data->count ) ) {
			echo sprintf( '<span class="font-variations-count">%d</span>', esc_html( $data->count ) );
		}

		return $post_states;
	}

	/**
	 * Render preview column in font manager admin listing
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function render_columns( $column, $post_id ) {
		if ( 'icons_prefix' === $column ) {
			$data = json_decode( self::get_icon_set_config( $post_id ) );
			if ( ! empty( $data->prefix ) ) {
				echo '<pre>' . esc_html( '.' . $data->prefix ) . '</pre>';
			}
		}
	}

	/**
	 * Define which columns to display in font manager admin listing
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function manage_columns( $columns ) {
		return [
			'cb' => '<input type="checkbox" />',
			'title' => esc_html__( 'Icon Set', 'modulix' ),
			'icons_prefix' => esc_html__( 'CSS Prefix', 'modulix' ),
		];
	}

	public function update_enter_title_here( $title, $post ) {
		if ( isset( $post->post_type ) && Icons_Manager::CPT === $post->post_type ) {
			return esc_html__( 'Enter Icon Set Name', 'modulix' );
		}

		return $title;
	}

	public function register_ajax_actions( Ajax $ajax ) {
		$ajax->register_ajax_action( 'pro_assets_manager_custom_icon_upload', [ $this, 'custom_icons_upload_handler' ] );
	}

	public function register_icon_libraries_control( $additional_sets ) {
		return array_replace( $additional_sets, self::get_custom_icons_config() );
	}

	public function add_custom_icon_templates( $current_screen ) {
		if ( 'elementor_icons' !== $current_screen->id || 'post' !== $current_screen->base ) {
			return;
		}
		Plugin::elementor()->common->add_template( __DIR__ . '/templates.php' );
	}

	public function add_custom_icons_url( $config ) {
		$config['customIconsURL'] = admin_url( 'edit.php?post_type=' . Icons_Manager::CPT );
		return $config;
	}

	public static function get_custom_icons_config() {
		$config = get_option( self::OPTION_NAME, false );
		if ( false === $config ) {
			$icons = new \WP_Query( [
				'post_type' => Icons_Manager::CPT,
				'posts_per_page' => -1,
				'post_status' => 'publish',
			] );
			$config = [];
			foreach ( $icons->posts as $icon_set ) {
				$set_config = json_decode( self::get_icon_set_config( $icon_set->ID ), true );
				$set_config['custom_icon_post_id'] = $icon_set->ID;
				$set_config['label'] = $icon_set->post_title;
				if ( isset( $set_config['fetchJson'] ) ) {
					unset( $set_config['icons'] );
				}
				$config[ $set_config['name'] ] = $set_config;
			}
			update_option( self::OPTION_NAME, $config );
		}
		return $config;
	}

	public static function icon_set_prefix_exists( $prefix ) {
		$config = self::get_custom_icons_config();
		if ( empty( $config ) ) {
			return false;
		}
		foreach ( $config as $icon_set_name => $icon_config ) {
			if ( $prefix === $icon_config['prefix'] ) {
				return true;
			}
		}
		return false;
	}

	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( Icons_Manager::CPT !== $post->post_type ) {
			return;
		}

		if ( 'publish' === $old_status && 'publish' !== $new_status ) {
			$this->clear_icon_list_option();
		}
	}

	protected function actions() {

		parent::actions();
		if ( is_admin() ) {
			add_action( 'add_meta_boxes_' . Icons_Manager::CPT, [ $this, 'add_meta_box' ] );
			add_action( 'save_post_' . Icons_Manager::CPT, [ $this, 'save_post_meta' ], 10, 3 );
			add_filter( 'display_post_states', [ $this, 'display_post_states' ], 10, 2 );
			add_action( 'manage_' . Icons_Manager::CPT . '_posts_custom_column', [ $this, 'render_columns' ], 10, 2 );
			add_filter( 'enter_title_here', [ $this, 'update_enter_title_here' ], 10, 2 );
			add_filter( 'manage_' . Icons_Manager::CPT . '_posts_columns', [ $this, 'manage_columns' ], 100 );
			add_action( 'current_screen', [ $this, 'add_custom_icon_templates' ] );
		}

		add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'handle_delete_icon_set' ] );
		add_filter( 'elementor/icons_manager/additional_tabs', [ $this, 'register_icon_libraries_control' ] );
		add_filter( 'elementor/editor/localize_settings', [ $this, 'add_custom_icons_url' ] );

		// Ajax.
		add_action( 'elementor/ajax/register_actions', [ $this, 'register_ajax_actions' ] );
	}
}
