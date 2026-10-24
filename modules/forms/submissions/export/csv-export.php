<?php
namespace Modulix\Modules\Forms\Submissions\Export;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/submissions/export/csv-export.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Core\Base\Base_Object;
use Elementor\Core\Utils\Collection;
use Modulix\Modules\Forms\Submissions\Database\Entities\Form_Snapshot;
use Modulix\Modules\Forms\Submissions\Database\Query;
use Modulix\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository;
use Modulix\Security\CsvCell;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class CSV_Export extends Base_Object {
	/**
	 * @var Collection
	 */
	private $submissions;

	/**
	 * @var integer
	 */
	private $post_id;

	/**
	 * @var string
	 */
	private $element_id;

	/**
	 * @var Form_Snapshot|null
	 */
	private $form;

	/**
	 * @var Collection
	 */
	private $values_keys;

	/**
	 * Csv_Export constructor.
	 *
	 * Csv_Export constructor.
	 *
	 * @param Collection $submissions
	 */
	public function __construct( Collection $submissions ) {
		$this->submissions = $submissions;

		$first_submission = $this->submissions->first();

		$this->values_keys = new Collection( [] );
		$this->post_id = $first_submission['post']['id'];
		$this->element_id = $first_submission['element_id'];

		$this->form = Form_Snapshot_Repository::instance()->find(
			$this->post_id,
			$this->element_id
		);
	}

	/**
	 * @return array
	 */
	public function prepare_for_json_response() {
		$this->values_keys = Query::get_instance()->get_submissions_value_keys(
			$this->post_id,
			$this->element_id
		);

		$headers = $this->get_headers();
		$rows = $this->get_rows();

		return [
			'id' => $this->element_id,
			'content' => array_merge( $headers, $rows ),
			'mimetype' => 'text/csv;charset=UTF-8',
			'extension' => 'csv',
			'form_label' => $this->form ? $this->form->get_label() : "({$this->element_id})",
		];
	}

	/**
	 * @return array
	 */
	private function get_headers() {
		$base_headers = [
			'1_form_name' => $this->encode_cell( esc_html__( 'Form Name (ID)', 'modulix' ) ),
			'2_id' => $this->encode_cell( esc_html__( 'Submission ID', 'modulix' ) ),
			'3_created_at' => $this->encode_cell( esc_html__( 'Created At', 'modulix' ) ),
			'4_user_id' => $this->encode_cell( esc_html__( 'User ID', 'modulix' ) ),
			'5_user_agent' => $this->encode_cell( esc_html__( 'User Agent', 'modulix' ) ),
			'6_user_ip' => $this->encode_cell( esc_html__( 'User IP', 'modulix' ) ),
			'7_referrer' => $this->encode_cell( esc_html__( 'Referrer', 'modulix' ) ),
		];

		$labels_dictionary = $this->get_form_labels_dictionary();

		$headers = $this->values_keys
			->map_with_keys( function ( $key ) use ( $labels_dictionary ) {
				return [
					$key => $this->encode_cell( isset( $labels_dictionary[ $key ] ) ? $labels_dictionary[ $key ] : $key ),
				];
			} )
			->merge( $base_headers )
			->all();

		return [ implode( ',', $headers ) ];
	}

	/**
	 * @return array
	 */
	private function get_rows() {
		return $this->submissions->map( function ( $submission ) {
			$base_values = [
				'1_form_name' => $this->encode_cell(
					$this->form ? $this->form->get_label() : "({$this->element_id})"
				),
				'2_id' => $this->encode_cell( $submission['id'] ),
				'3_created_at' => $this->encode_cell( $submission['created_at'] ),
				'4_user_id' => $this->encode_cell( $submission['user_id'] ),
				'5_user_agent' => $this->encode_cell( $submission['user_agent'] ),
				'6_user_ip' => $this->encode_cell( $submission['user_ip'] ),
				'7_referrer' => $this->encode_cell( $submission['referer'] ),
			];

			$values_dictionary = $this->get_values_dictionary( $submission['values'] );

			$row = $this->values_keys
				->map_with_keys( function ( $key ) use ( $values_dictionary ) {
					return [
						$key => $this->encode_cell( isset( $values_dictionary[ $key ] ) ? $values_dictionary[ $key ] : '' ),
					];
				} )
				->merge( $base_values )
				->all();

			return implode( ',', $row );
		} )->all();
	}

	private function encode_cell( $value ) {
		return CsvCell::encode( (string) $value );
	}

	/**
	 * Create a dictionary from the field id and label.
	 *
	 * @return array
	 */
	private function get_form_labels_dictionary() {
		if ( ! $this->form ) {
			return [];
		}

		$dictionary = [];

		foreach ( $this->form->fields as $field ) {
			$dictionary[ $field['id'] ] = $field['label'];
		}

		return $dictionary;
	}

	/**
	 * Create a dictionary from the value record key and value.
	 *
	 * @param array $values
	 *
	 * @return array
	 */
	private function get_values_dictionary( $values ) {
		if ( ! $values ) {
			return [];
		}

		$dictionary = [];

		foreach ( $values as $value ) {
			$dictionary[ $value['key'] ] = $value['value'];
		}

		return $dictionary;
	}
}
