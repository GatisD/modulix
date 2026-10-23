<?php
namespace Modulix\Modules\QueryControl\Controls;
// Modulix: derived from Elementor Pro 4.0.4, modules/query-control/controls/template-query.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Control_Select2;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Template_Query extends Control_Select2 {

	const CONTROL_ID = 'template_query';

	/**
	 * @return string
	 */
	public function get_type() {
		return static::CONTROL_ID;
	}

	public function content_template() {
		parent::content_template();
		?>
			<div class="elementor-template-query-control-actions">
				<# if ( data.actions.new.visible ) { #>
					<button
						type="button"
						class="elementor-button"
						data-action="new"
						data-after-action="{{ data.actions.new.after_action }}"
					>
						{{ data.actions.new.label }}
					</button>
				<# } #>
				<# if ( data.actions.edit.visible ) { #>
					<button
						type="button"
						class="elementor-button"
						data-action="edit"
						data-after-action="{{ data.actions.edit.after_action }}"
					>
						{{ data.actions.edit.label }}
					</button>
				<# } #>
			</div>
		<?php
	}

	/**
	 * @return array
	 */
	protected function get_default_settings() {
		return array_replace_recursive(
			parent::get_default_settings(),
			[
				'query' => '',
				'actions' => [
					'new' => [
						'visible' => false,
						'label' => __( 'Create template', 'modulix' ),
						'document_config' => [
							'type' => null,
						],
						'after_action' => 'switch_document',
					],
					'edit' => [
						'visible' => false,
						'label' => __( 'Edit template', 'modulix' ),
						'after_action' => 'switch_document',
					],
				],
			]
		);
	}
}
