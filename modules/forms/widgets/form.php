<?php
namespace Modulix\Modules\Forms\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/forms/widgets/form.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Modulix\Core\Utils;
use Modulix\Modules\Forms\Classes\Ajax_Handler;
use Modulix\Modules\Forms\Classes\Form_Base;
use Modulix\Modules\Forms\Controls\Fields_Repeater;
use Modulix\Modules\Forms\Module;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Form extends Form_Base {
	public function get_name() {
		return 'form';
	}

	public function get_title() {
		return esc_html__( 'Form', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_keywords() {
		return [ 'form', 'forms', 'field', 'button', 'mailchimp', 'drip', 'mailpoet', 'convertkit', 'getresponse', 'recaptcha', 'zapier', 'webhook', 'activecampaign', 'slack', 'discord', 'mailerlite' ];
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
	}

	/**
	 * Get style dependencies.
	 *
	 * Retrieve the list of style dependencies the widget requires.
	 *
	 * @since 3.24.0
	 * @access public
	 *
	 * @return array Widget style dependencies.
	 */
	public function get_style_depends(): array {
		return [ 'widget-form' ];
	}

	protected function register_controls() {
		$repeater = new Repeater();

		$field_types = [
			'text' => esc_html__( 'Text', 'modulix' ),
			'email' => esc_html__( 'Email', 'modulix' ),
			'textarea' => esc_html__( 'Textarea', 'modulix' ),
			'url' => esc_html__( 'URL', 'modulix' ),
			'tel' => esc_html__( 'Tel', 'modulix' ),
			'radio' => esc_html__( 'Radio', 'modulix' ),
			'select' => esc_html__( 'Select', 'modulix' ),
			'checkbox' => esc_html__( 'Checkbox', 'modulix' ),
			'acceptance' => esc_html__( 'Acceptance', 'modulix' ),
			'number' => esc_html__( 'Number', 'modulix' ),
			'date' => esc_html__( 'Date', 'modulix' ),
			'time' => esc_html__( 'Time', 'modulix' ),
			'upload' => esc_html__( 'File Upload', 'modulix' ),
			'password' => esc_html__( 'Password', 'modulix' ),
			'html' => esc_html__( 'HTML', 'modulix' ),
			'hidden' => esc_html__( 'Hidden', 'modulix' ),
		];

		/**
		 * Forms field types.
		 *
		 * Filters the list of field types displayed in the form `field_type` control.
		 *
		 * This hook allows developers to alter the list of displayed field types. For
		 * example, removing the 'upload' field type from the list of fields types will
		 * prevent uploading files using Elementor forms.
		 *
		 * @since 1.0.0
		 *
		 * @param array $field_types Field types.
		 */
		$field_types = apply_filters( 'elementor_pro/forms/field_types', $field_types );

		$repeater->start_controls_tabs( 'form_fields_tabs' );

		$repeater->start_controls_tab( 'form_fields_content_tab', [
			'label' => esc_html__( 'Content', 'modulix' ),
		] );

		$repeater->add_control(
			'field_type',
			[
				'label' => esc_html__( 'Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => $field_types,
				'default' => 'text',
			]
		);

		$repeater->add_control(
			'field_label',
			[
				'label' => esc_html__( 'Label', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'placeholder',
			[
				'label' => esc_html__( 'Placeholder', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'operator' => 'in',
							'value' => [
								'tel',
								'text',
								'email',
								'textarea',
								'number',
								'url',
								'password',
							],
						],
					],
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'required',
			[
				'label' => esc_html__( 'Required', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'default' => '',
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'operator' => '!in',
							'value' => [
								'checkbox',
								'recaptcha',
								'recaptcha_v3',
								'hidden',
								'html',
								'step',
							],
						],
					],
				],
			]
		);

		$repeater->add_control(
			'field_options',
			[
				'label' => esc_html__( 'Options', 'modulix' ),
				'type' => Controls_Manager::TEXTAREA,
				'default' => '',
				'description' => esc_html__( 'Enter each option in a separate line. To differentiate between label and value, separate them with a pipe char ("|"). For example: First Name|f_name', 'modulix' ),
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'operator' => 'in',
							'value' => [
								'select',
								'checkbox',
								'radio',
							],
						],
					],
				],
			]
		);

		$repeater->add_control(
			'allow_multiple',
			[
				'label' => esc_html__( 'Multiple Selection', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'value' => 'select',
						],
					],
				],
			]
		);

		$repeater->add_control(
			'select_size',
			[
				'label' => esc_html__( 'Rows', 'modulix' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 2,
				'step' => 1,
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'value' => 'select',
						],
						[
							'name' => 'allow_multiple',
							'value' => 'true',
						],
					],
				],
			]
		);

		$repeater->add_control(
			'inline_list',
			[
				'label' => esc_html__( 'Inline List', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'elementor-subgroup-inline',
				'default' => '',
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'operator' => 'in',
							'value' => [
								'checkbox',
								'radio',
							],
						],
					],
				],
			]
		);

		$repeater->add_control(
			'field_html',
			[
				'label' => esc_html__( 'HTML', 'modulix' ),
				'type' => Controls_Manager::TEXTAREA,
				'dynamic' => [
					'active' => true,
				],
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'value' => 'html',
						],
					],
				],
			]
		);

		$repeater->add_responsive_control(
			'width',
			[
				'label' => esc_html__( 'Column Width', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => esc_html__( 'Default', 'modulix' ),
					'100' => '100%',
					'80' => '80%',
					'75' => '75%',
					'70' => '70%',
					'66' => '66%',
					'60' => '60%',
					'50' => '50%',
					'40' => '40%',
					'33' => '33%',
					'30' => '30%',
					'25' => '25%',
					'20' => '20%',
				],
				'default' => '100',
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'operator' => '!in',
							'value' => [
								'hidden',
								'recaptcha',
								'recaptcha_v3',
								'step',
							],
						],
					],
				],
			]
		);

		$repeater->add_control(
			'rows',
			[
				'label' => esc_html__( 'Rows', 'modulix' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 4,
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'value' => 'textarea',
						],
					],
				],
			]
		);

		$repeater->add_control(
			'recaptcha_size', [
				'label' => esc_html__( 'Size', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'normal',
				'options' => [
					'normal' => esc_html__( 'Normal', 'modulix' ),
					'compact' => esc_html__( 'Compact', 'modulix' ),
				],
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'value' => 'recaptcha',
						],
					],
				],
			]
		);

		$repeater->add_control(
			'recaptcha_style',
			[
				'label' => esc_html__( 'Style', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'light',
				'options' => [
					'light' => esc_html__( 'Light', 'modulix' ),
					'dark' => esc_html__( 'Dark', 'modulix' ),
				],
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'value' => 'recaptcha',
						],
					],
				],
			]
		);

		$repeater->add_control(
			'recaptcha_badge', [
				'label' => esc_html__( 'Badge', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'bottomright',
				'options' => [
					'bottomright' => esc_html__( 'Bottom Right', 'modulix' ),
					'bottomleft' => esc_html__( 'Bottom Left', 'modulix' ),
					'inline' => esc_html__( 'Inline', 'modulix' ),
				],
				'description' => esc_html__( 'To view the validation badge, switch to preview mode', 'modulix' ),
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'value' => 'recaptcha_v3',
						],
					],
				],
			]
		);

		$repeater->add_control(
			'css_classes',
			[
				'label' => esc_html__( 'CSS Classes', 'modulix' ),
				'type' => Controls_Manager::HIDDEN,
				'default' => '',
				'title' => esc_html__( 'Add your custom class WITHOUT the dot. e.g: my-class', 'modulix' ),
			]
		);

		$repeater->end_controls_tab();

		$repeater->start_controls_tab(
			'form_fields_advanced_tab',
			[
				'label' => esc_html__( 'Advanced', 'modulix' ),
				'condition' => [
					'field_type!' => 'html',
				],
			]
		);

		$repeater->add_control(
			'field_value',
			[
				'label' => esc_html__( 'Default Value', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'dynamic' => [
					'active' => true,
				],
				'ai' => [
					'active' => false,
				],
				'conditions' => [
					'terms' => [
						[
							'name' => 'field_type',
							'operator' => 'in',
							'value' => [
								'text',
								'email',
								'textarea',
								'url',
								'tel',
								'radio',
								'select',
								'number',
								'date',
								'time',
								'hidden',
							],
						],
					],
				],
			]
		);

		$repeater->add_control(
			'custom_id',
			[
				'label' => esc_html__( 'ID', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'description' => sprintf(
					esc_html__( 'Please make sure the ID is unique and not used elsewhere on the page. This field allows %1$sA-z 0-9%2$s & underscore chars without spaces.', 'modulix' ),
					'<code>',
					'</code>'
				),
				'render_type' => 'none',
				'required' => true,
				'dynamic' => [
					'active' => true,
				],
				'ai' => [
					'active' => false,
				],
			]
		);

		$shortcode_template = '{{ view.container.settings.get( \'custom_id\' ) }}';
		$repeater->add_control(
			'shortcode',
			[
				'label' => esc_html__( 'Shortcode', 'modulix' ),
				'type' => Controls_Manager::RAW_HTML,
				'classes' => 'forms-field-shortcode',
				'raw' => '<input class="elementor-form-field-shortcode" value=\'[field id="' . $shortcode_template . '"]\' readonly />',
			]
		);

		$repeater->end_controls_tab();

		$repeater->end_controls_tabs();

		$this->start_controls_section(
			'section_form_fields',
			[
				'label' => esc_html__( 'Form Fields', 'modulix' ),
			]
		);

		$this->add_control(
			'form_name',
			[
				'label' => esc_html__( 'Form Name', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'New Form', 'modulix' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'form_fields',
			[
				'type' => Fields_Repeater::CONTROL_TYPE,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'custom_id' => 'name',
						'field_type' => 'text',
						'field_label' => esc_html__( 'Name', 'modulix' ),
						'placeholder' => esc_html__( 'Name', 'modulix' ),
						'width' => '100',
						'dynamic' => [
							'active' => true,
						],
					],
					[
						'custom_id' => 'email',
						'field_type' => 'email',
						'required' => 'true',
						'field_label' => esc_html__( 'Email', 'modulix' ),
						'placeholder' => esc_html__( 'Email', 'modulix' ),
						'width' => '100',
					],
					[
						'custom_id' => 'message',
						'field_type' => 'textarea',
						'field_label' => esc_html__( 'Message', 'modulix' ),
						'placeholder' => esc_html__( 'Message', 'modulix' ),
						'width' => '100',
					],
				],
				'title_field' => '{{{ field_label }}}',
			]
		);

		$this->add_control(
			'input_size',
			[
				'label' => esc_html__( 'Input Size', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'xs' => esc_html__( 'Extra Small', 'modulix' ),
					'sm' => esc_html__( 'Small', 'modulix' ),
					'md' => esc_html__( 'Medium', 'modulix' ),
					'lg' => esc_html__( 'Large', 'modulix' ),
					'xl' => esc_html__( 'Extra Large', 'modulix' ),
				],
				'default' => 'sm',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'show_labels',
			[
				'label' => esc_html__( 'Label', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'modulix' ),
				'label_off' => esc_html__( 'Hide', 'modulix' ),
				'return_value' => 'true',
				'default' => 'true',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'mark_required',
			[
				'label' => esc_html__( 'Required Mark', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'modulix' ),
				'label_off' => esc_html__( 'Hide', 'modulix' ),
				'default' => '',
				'condition' => [
					'show_labels!' => '',
				],
			]
		);

		$this->add_control(
			'label_position',
			[
				'label' => esc_html__( 'Label Position', 'modulix' ),
				'type' => Controls_Manager::HIDDEN,
				'options' => [
					'above' => esc_html__( 'Above', 'modulix' ),
					'inline' => esc_html__( 'Inline', 'modulix' ),
				],
				'default' => 'above',
				'condition' => [
					'show_labels!' => '',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_buttons',
			[
				'label' => esc_html__( 'Buttons', 'modulix' ),
			]
		);

		$this->add_control(
			'button_size',
			[
				'label' => esc_html__( 'Size', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'sm',
				'options' => self::get_button_sizes(),
			]
		);

		$this->add_responsive_control(
			'button_width',
			[
				'label' => esc_html__( 'Column Width', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => esc_html__( 'Default', 'modulix' ),
					'100' => '100%',
					'80' => '80%',
					'75' => '75%',
					'70' => '70%',
					'66' => '66%',
					'60' => '60%',
					'50' => '50%',
					'40' => '40%',
					'33' => '33%',
					'30' => '30%',
					'25' => '25%',
					'20' => '20%',
				],
				'default' => '100',
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'heading_steps_buttons',
			[
				'label' => esc_html__( 'Step Buttons', 'modulix' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'step_next_label',
			[
				'label' => esc_html__( 'Next', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'ai' => [
					'active' => false,
				],
				'frontend_available' => true,
				'render_type' => 'none',
				'default' => esc_html__( 'Next', 'modulix' ),
				'placeholder' => esc_html__( 'Next', 'modulix' ),
			]
		);

		$this->add_control(
			'step_previous_label',
			[
				'label' => esc_html__( 'Previous', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'ai' => [
					'active' => false,
				],
				'frontend_available' => true,
				'render_type' => 'none',
				'default' => esc_html__( 'Previous', 'modulix' ),
				'placeholder' => esc_html__( 'Previous', 'modulix' ),
			]
		);

		$this->add_control(
			'heading_submit_button',
			[
				'label' => esc_html__( 'Submit Button', 'modulix' ),
				'type' => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'button_text',
			[
				'label' => esc_html__( 'Submit', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Send', 'modulix' ),
				'placeholder' => esc_html__( 'Send', 'modulix' ),
				'dynamic' => [
					'active' => true,
				],
				'ai' => [
					'active' => false,
				],
			]
		);

		$this->add_control(
			'selected_button_icon',
			[
				'label' => esc_html__( 'Icon', 'modulix' ),
				'type' => Controls_Manager::ICONS,
				'skin' => 'inline',
				'label_block' => false,
			]
		);

		$start = is_rtl() ? 'right' : 'left';
		$end = is_rtl() ? 'left' : 'right';

		$this->add_control(
			'button_icon_align',
			[
				'label' => esc_html__( 'Icon Position', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'default' => is_rtl() ? 'row-reverse' : 'row',
				'options' => [
					'row' => [
						'title' => esc_html__( 'Start', 'modulix' ),
						'icon' => "eicon-h-align-{$start}",
					],
					'row-reverse' => [
						'title' => esc_html__( 'End', 'modulix' ),
						'icon' => "eicon-h-align-{$end}",
					],
				],
				'selectors_dictionary' => [
					'left' => is_rtl() ? 'row-reverse' : 'row',
					'right' => is_rtl() ? 'row' : 'row-reverse',
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-button-content-wrapper' => 'flex-direction: {{VALUE}};',
				],
				'condition' => [
					'button_text!' => '',
					'selected_button_icon[value]!' => '',
				],
			]
		);

		$this->add_control(
			'button_icon_indent',
			[
				'label' => esc_html__( 'Icon Spacing', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 10,
					],
					'rem' => [
						'max' => 10,
					],
				],
				'condition' => [
					'button_text!' => '',
					'selected_button_icon[value]!' => '',
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-button span' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_css_id',
			[
				'label' => esc_html__( 'Button ID', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'ai' => [
					'active' => false,
				],
				'title' => esc_html__( 'Add your custom id WITHOUT the Pound key. e.g: my-id', 'modulix' ),
				'description' => sprintf(
					esc_html__( 'Please make sure the ID is unique and not used elsewhere on the page. This field allows %1$sA-z 0-9%2$s & underscore chars without spaces.', 'modulix' ),
					'<code>',
					'</code>'
				),
				'separator' => 'before',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_integration',
			[
				'label' => esc_html__( 'Actions After Submit', 'modulix' ),
			]
		);

		$actions = Module::instance()->actions_registrar->get();

		$actions_options = [];

		foreach ( $actions as $action ) {
			$actions_options[ $action->get_name() ] = $action->get_label();
		}

		$default_submit_actions = [ 'email' ];

		/**
		 * Default submit actions.
		 *
		 * Filters the list of submit actions pre deffined by Elementor forms.
		 *
		 * By default, only one submit action is set by Elementor forms, an 'email'
		 * action. This hook allows developers to alter those submit action.
		 *
		 * @param array $default_submit_actions A list of default submit actions.
		 */
		$default_submit_actions = apply_filters( 'elementor_pro/forms/default_submit_actions', $default_submit_actions );

		$this->add_control(
			'submit_actions',
			[
				'label' => esc_html__( 'Add Action', 'modulix' ),
				'type' => Controls_Manager::SELECT2,
				'multiple' => true,
				'options' => $actions_options,
				'render_type' => 'none',
				'label_block' => true,
				'default' => $default_submit_actions,
				'description' => esc_html__( 'Add actions that will be performed after a visitor submits the form (e.g. send an email notification). Choosing an action will add its setting below.', 'modulix' ),
			]
		);

		$this->end_controls_section();

		foreach ( $actions as $action ) {
			$action->register_settings_section( $this );
		}

		// Steps settings.
		$this->start_controls_section(
			'section_steps_settings',
			[
				'label' => esc_html__( 'Steps Settings', 'modulix' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'step_type',
			[
				'label' => esc_html__( 'Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'frontend_available' => true,
				'render_type' => 'none',
				'options' => [
					'none' => esc_html__( 'None', 'modulix' ),
					'text' => esc_html__( 'Text', 'modulix' ),
					'icon' => esc_html__( 'Icon', 'modulix' ),
					'number' => esc_html__( 'Number', 'modulix' ),
					'progress_bar' => esc_html__( 'Progress Bar', 'modulix' ),
					'number_text' => esc_html__( 'Number & Text', 'modulix' ),
					'icon_text' => esc_html__( 'Icon & Text', 'modulix' ),
				],
				'default' => 'number_text',
			]
		);

		$this->add_control(
			'step_icon_shape',
			[
				'label' => esc_html__( 'Shape', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'frontend_available' => true,
				'render_type' => 'none',
				'options' => [
					'circle' => esc_html__( 'Circle', 'modulix' ),
					'square' => esc_html__( 'Square', 'modulix' ),
					'rounded' => esc_html__( 'Rounded', 'modulix' ),
					'none' => 'None',
				],
				'default' => 'circle',
				'conditions' => [
					'terms' => [
						[
							'name' => 'step_type',
							'operator' => '!in',
							'value' => [
								'progress_bar',
								'text',
							],
						],
					],
				],
			]
		);

		$repeater->add_control(
			'display_percentage',
			[
				'label' => esc_html__( 'Display Percentage', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'frontend_available' => true,
				'render_type' => 'none',
				'return_value' => 'true',
				'default' => '',
				'condition' => [
					'step_type' => 'progress_bar',
				],
			]
		);

		// End of steps settings.
		$this->end_controls_section();

		$this->start_controls_section(
			'section_form_options',
			[
				'label' => esc_html__( 'Additional Options', 'modulix' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'form_id',
			[
				'label' => esc_html__( 'Form ID', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'ai' => [
					'active' => false,
				],
				'placeholder' => 'new_form_id',
				'description' => sprintf(
					esc_html__( 'Please make sure the ID is unique and not used elsewhere on the page. This field allows %1$sA-z 0-9%2$s & underscore chars without spaces.', 'modulix' ),
					'<code>',
					'</code>'
				),
				'separator' => 'after',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'form_validation',
			[
				'label' => esc_html__( 'Form Validation', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => esc_html__( 'Browser Default', 'modulix' ),
					'custom' => esc_html__( 'Custom', 'modulix' ),
				],
				'default' => '',
			]
		);

		$this->add_control(
			'custom_messages',
			[
				'label' => esc_html__( 'Custom Messages', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'separator' => 'before',
				'render_type' => 'none',
			]
		);

		$default_messages = Ajax_Handler::get_default_messages();

		$this->add_control(
			'success_message',
			[
				'label' => esc_html__( 'Success Message', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => $default_messages[ Ajax_Handler::SUCCESS ],
				'placeholder' => $default_messages[ Ajax_Handler::SUCCESS ],
				'label_block' => true,
				'condition' => [
					'custom_messages!' => '',
				],
				'render_type' => 'none',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'error_message',
			[
				'label' => esc_html__( 'Form Error', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => $default_messages[ Ajax_Handler::ERROR ],
				'placeholder' => $default_messages[ Ajax_Handler::ERROR ],
				'label_block' => true,
				'condition' => [
					'custom_messages!' => '',
				],
				'render_type' => 'none',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'server_message',
			[
				'label' => esc_html__( 'Server Error', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => $default_messages[ Ajax_Handler::SERVER_ERROR ],
				'placeholder' => $default_messages[ Ajax_Handler::SERVER_ERROR ],
				'label_block' => true,
				'condition' => [
					'custom_messages!' => '',
				],
				'render_type' => 'none',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'invalid_message',
			[
				'label' => esc_html__( 'Invalid Form', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => $default_messages[ Ajax_Handler::INVALID_FORM ],
				'placeholder' => $default_messages[ Ajax_Handler::INVALID_FORM ],
				'label_block' => true,
				'condition' => [
					'custom_messages!' => '',
				],
				'render_type' => 'none',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'required_field_message',
			[
				'label' => esc_html__( 'Required Field', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => $default_messages[ Ajax_Handler::FIELD_REQUIRED ],
				'placeholder' => $default_messages[ Ajax_Handler::FIELD_REQUIRED ],
				'label_block' => true,
				'condition' => [
					'custom_messages!' => '',
					'form_validation' => 'custom',
				],
				'render_type' => 'none',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_form_style',
			[
				'label' => esc_html__( 'Form', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'column_gap',
			[
				'label' => esc_html__( 'Columns Gap', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default' => [
					'size' => 10,
				],
				'range' => [
					'px' => [
						'max' => 60,
					],
					'em' => [
						'max' => 6,
					],
					'rem' => [
						'max' => 6,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group' => 'padding-right: calc( {{SIZE}}{{UNIT}}/2 ); padding-left: calc( {{SIZE}}{{UNIT}}/2 );',
					'{{WRAPPER}} .elementor-form-fields-wrapper' => 'margin-left: calc( -{{SIZE}}{{UNIT}}/2 ); margin-right: calc( -{{SIZE}}{{UNIT}}/2 );',
				],
			]
		);

		$this->add_control(
			'row_gap',
			[
				'label' => esc_html__( 'Rows Gap', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default' => [
					'size' => 10,
				],
				'range' => [
					'px' => [
						'max' => 60,
					],
					'em' => [
						'max' => 6,
					],
					'rem' => [
						'max' => 6,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group' => 'margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .elementor-field-group.recaptcha_v3-bottomleft, {{WRAPPER}} .elementor-field-group.recaptcha_v3-bottomright' => 'margin-bottom: 0;',
					'{{WRAPPER}} .elementor-form-fields-wrapper' => 'margin-bottom: -{{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'heading_label',
			[
				'label' => esc_html__( 'Label', 'modulix' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'label_spacing',
			[
				'label' => esc_html__( 'Spacing', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'max' => 60,
					],
					'em' => [
						'max' => 6,
					],
					'rem' => [
						'max' => 6,
					],
				],
				'selectors' => [
					'body.rtl {{WRAPPER}} .elementor-labels-inline .elementor-field-group > label' => 'padding-left: {{SIZE}}{{UNIT}};',
					// for the label position = inline option
					'body:not(.rtl) {{WRAPPER}} .elementor-labels-inline .elementor-field-group > label' => 'padding-right: {{SIZE}}{{UNIT}};',
					// for the label position = inline option
					'body {{WRAPPER}} .elementor-labels-above .elementor-field-group > label' => 'padding-bottom: {{SIZE}}{{UNIT}};',
					// for the label position = above option
				],
			]
		);

		$this->add_control(
			'label_color',
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group > label, {{WRAPPER}} .elementor-field-subgroup label' => 'color: {{VALUE}};',
				],
				'global' => [
					'default' => Global_Colors::COLOR_TEXT,
				],
			]
		);

		$this->add_control(
			'mark_required_color',
			[
				'label' => esc_html__( 'Mark Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .elementor-mark-required .elementor-field-label:after' => 'color: {{COLOR}};',
				],
				'condition' => [
					'mark_required' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'label_typography',
				'selector' => '{{WRAPPER}} .elementor-field-group > label',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
			]
		);

		$this->add_control(
			'heading_html',
			[
				'label' => esc_html__( 'HTML Field', 'modulix' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'html_spacing',
			[
				'label' => esc_html__( 'Spacing', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'max' => 60,
					],
					'em' => [
						'max' => 6,
					],
					'rem' => [
						'max' => 6,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-field-type-html' => 'padding-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'html_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-field-type-html' => 'color: {{VALUE}};',
				],
				'global' => [
					'default' => Global_Colors::COLOR_TEXT,
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'html_typography',
				'selector' => '{{WRAPPER}} .elementor-field-type-html',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_field_style',
			[
				'label' => esc_html__( 'Field', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'field_text_color',
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group .elementor-field' => 'color: {{VALUE}};',
				],
				'global' => [
					'default' => Global_Colors::COLOR_TEXT,
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'field_typography',
				'selector' => '{{WRAPPER}} .elementor-field-group .elementor-field, {{WRAPPER}} .elementor-field-subgroup label',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
			]
		);

		$this->add_control(
			'field_background_color',
			[
				'label' => esc_html__( 'Background Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group .elementor-field:not(.elementor-select-wrapper)' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .elementor-field-group .elementor-select-wrapper select' => 'background-color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'field_border_color',
			[
				'label' => esc_html__( 'Border Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group .elementor-field:not(.elementor-select-wrapper)' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .elementor-field-group .elementor-select-wrapper select' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .elementor-field-group .elementor-select-wrapper::before' => 'color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'field_border_width',
			[
				'label' => esc_html__( 'Border Width', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'placeholder' => '1',
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group .elementor-field:not(.elementor-select-wrapper)' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .elementor-field-group .elementor-select-wrapper select' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'field_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group .elementor-field:not(.elementor-select-wrapper)' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .elementor-field-group .elementor-select-wrapper select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_button_style',
			[
				'label' => esc_html__( 'Buttons', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'button_align',
			[
				'label' => esc_html__( 'Position', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'start' => [
						'title' => esc_html__( 'Left', 'modulix' ),
						'icon' => 'eicon-h-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'modulix' ),
						'icon' => 'eicon-h-align-center',
					],
					'end' => [
						'title' => esc_html__( 'Right', 'modulix' ),
						'icon' => 'eicon-h-align-right',
					],
					'stretch' => [
						'title' => esc_html__( 'Stretch', 'modulix' ),
						'icon' => 'eicon-h-align-stretch',
					],
				],
				'default' => 'stretch',
				'prefix_class' => 'elementor%s-button-align-',
			]
		);

		$this->add_responsive_control(
			'button_content_align',
			[
				'label' => esc_html__( 'Alignment', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'start'    => [
						'title' => esc_html__( 'Start', 'modulix' ),
						'icon' => "eicon-text-align-{$start}",
					],
					'center' => [
						'title' => esc_html__( 'Center', 'modulix' ),
						'icon' => 'eicon-text-align-center',
					],
					'end' => [
						'title' => esc_html__( 'End', 'modulix' ),
						'icon' => "eicon-text-align-{$end}",
					],
					'space-between' => [
						'title' => esc_html__( 'Space between', 'modulix' ),
						'icon' => 'eicon-text-align-justify',
					],
				],
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .elementor-button span' => 'justify-content: {{VALUE}};',
				],
				'condition' => [ 'button_align' => 'stretch' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'button_typography',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_ACCENT,
				],
				'selector' => '{{WRAPPER}} .elementor-button',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(), [
				'name' => 'button_border',
				'selector' => '{{WRAPPER}} .elementor-button',
				'exclude' => [
					'color',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		$this->start_controls_tab(
			'tab_button_normal',
			[
				'label' => esc_html__( 'Normal', 'modulix' ),
			]
		);

		$this->add_control(
			'heading_next_submit_button',
			[
				'label' => esc_html__( 'Next & Submit Button', 'modulix' ),
				'type' => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'button_background_color',
			[
				'label' => esc_html__( 'Background Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_ACCENT,
				],
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-next' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .elementor-button[type="submit"]' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-next' => 'color: {{VALUE}};',
					'{{WRAPPER}} .elementor-button[type="submit"]' => 'color: {{VALUE}};',
					'{{WRAPPER}} .elementor-button[type="submit"] svg *' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_border_color',
			[
				'label' => esc_html__( 'Border Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-next' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .elementor-button[type="submit"]' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'button_border_border!' => '',
				],
			]
		);

		$this->add_control(
			'heading_previous_button',
			[
				'label' => esc_html__( 'Previous Button', 'modulix' ),
				'type' => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'previous_button_background_color',
			[
				'label' => esc_html__( 'Background Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_ACCENT,
				],
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-previous' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'previous_button_text_color',
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-previous' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'previous_button_border_color',
			[
				'label' => esc_html__( 'Border Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-previous' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'button_border_border!' => '',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			[
				'label' => esc_html__( 'Hover', 'modulix' ),
			]
		);

		$this->add_control(
			'heading_next_submit_button_hover',
			[
				'label' => esc_html__( 'Next & Submit Button', 'modulix' ),
				'type' => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'button_background_hover_color',
			[
				'label' => esc_html__( 'Background Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-next:hover' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .elementor-button[type="submit"]:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_color',
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-next:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .elementor-button[type="submit"]:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .elementor-button[type="submit"]:hover svg *' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_border_color',
			[
				'label' => esc_html__( 'Border Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-next:hover' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .elementor-button[type="submit"]:hover' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'button_border_border!' => '',
				],
			]
		);

		$this->add_control(
			'heading_previous_button_hover',
			[
				'label' => esc_html__( 'Previous Button', 'modulix' ),
				'type' => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'previous_button_background_color_hover',
			[
				'label' => esc_html__( 'Background Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-previous:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'previous_button_text_color_hover',
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-previous:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'previous_button_border_color_hover',
			[
				'label' => esc_html__( 'Border Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-previous:hover' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'button_border_border!' => '',
				],
			]
		);

		$this->add_control(
			'hover_transition_duration',
			[
				'label' => esc_html__( 'Transition Duration', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 's', 'ms', 'custom' ],
				'default' => [
					'unit' => 'ms',
				],
				'selectors' => [
					'{{WRAPPER}} .e-form__buttons__wrapper__button-previous' => 'transition-duration: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .e-form__buttons__wrapper__button-next' => 'transition-duration: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .elementor-button[type="submit"] svg *' => 'transition-duration: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .elementor-button[type="submit"]' => 'transition-duration: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'button_hover_animation',
			[
				'label' => esc_html__( 'Animation', 'modulix' ),
				'type' => Controls_Manager::HOVER_ANIMATION,
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'button_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .elementor-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'button_text_padding',
			[
				'label' => esc_html__( 'Text Padding', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_messages_style',
			[
				'label' => esc_html__( 'Messages', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'message_typography',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
				'selector' => '{{WRAPPER}} .elementor-message',
			]
		);

		$this->add_control(
			'success_message_color',
			[
				'label' => esc_html__( 'Success Message Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-message.elementor-message-success' => 'color: {{COLOR}};',
				],
			]
		);

		$this->add_control(
			'error_message_color',
			[
				'label' => esc_html__( 'Error Message Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-message.elementor-message-danger' => 'color: {{COLOR}};',
				],
			]
		);

		$this->add_control(
			'inline_message_color',
			[
				'label' => esc_html__( 'Inline Message Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-message.elementor-help-inline' => 'color: {{COLOR}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_steps_style',
			[
				'label' => esc_html__( 'Steps', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'steps_typography',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_ACCENT,
				],
				'selector' => '{{WRAPPER}} .e-form__indicators__indicator, {{WRAPPER}} .e-form__indicators__indicator__label',
				'conditions' => [
					'terms' => [
						[
							'name' => 'step_type',
							'operator' => '!in',
							'value' => [
								'icon',
								'progress_bar',
							],
						],
					],
				],
			]
		);

		$this->add_responsive_control(
			'steps_gap',
			[
				'label' => esc_html__( 'Spacing', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default' => [
					'size' => 20,
				],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 10,
					],
					'rem' => [
						'max' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicators-spacing: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'steps_icon_size',
			[
				'label' => esc_html__( 'Icon Size', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default' => [
					'size' => 15,
				],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 10,
					],
					'rem' => [
						'max' => 10,
					],
				],
				'conditions' => [
					'terms' => [
						[
							'name' => 'step_type',
							'operator' => 'in',
							'value' => [
								'icon',
								'icon_text',
							],
						],
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-icon-size: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'steps_padding',
			[
				'label' => esc_html__( 'Padding', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'default' => [
					'size' => 30,
				],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 10,
					],
					'rem' => [
						'max' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-padding: {{SIZE}}{{UNIT}}',
				],
				'conditions' => [
					'terms' => [
						[
							'name' => 'step_type',
							'operator' => '!in',
							'value' => [
								'text',
								'progress_bar',
							],
						],
					],
				],
			]
		);

		$this->start_controls_tabs( 'steps_state', [
			'condition' => [
				'step_type!' => 'progress_bar',
			],
		] );

		$this->start_controls_tab(
			'tab_steps_state_inactive',
			[
				'label' => esc_html__( 'Inactive', 'modulix' ),
			]
		);

		$this->add_control(
			'step_inactive_primary_color',
			[
				'label' => esc_html__( 'Primary Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_TEXT,
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-inactive-primary-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'step_inactive_secondary_color',
			[
				'label' => esc_html__( 'Secondary Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-inactive-secondary-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_steps_state_active',
			[
				'label' => esc_html__( 'Active', 'modulix' ),
			]
		);

		$this->add_control(
			'step_active_primary_color',
			[
				'label' => esc_html__( 'Primary Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_ACCENT,
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-active-primary-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'step_active_secondary_color',
			[
				'label' => esc_html__( 'Secondary Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-active-secondary-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_steps_state_completed',
			[
				'label' => esc_html__( 'Completed', 'modulix' ),
			]
		);

		$this->add_control(
			'step_completed_primary_color',
			[
				'label' => esc_html__( 'Primary Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_ACCENT,
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-completed-primary-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'step_completed_secondary_color',
			[
				'label' => esc_html__( 'Secondary Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'condition' => [
					'step_icon_shape!' => 'none',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-completed-secondary-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'step_divider_width',
			[
				'label' => esc_html__( 'Divider Width', 'modulix' ),
				'separator' => 'before',
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'default' => [
					'size' => 1,
				],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 10,
					],
					'rem' => [
						'max' => 10,
					],
				],
				'condition' => [
					'step_type!' => 'progress_bar',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-divider-width: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'step_divider_gap',
			[
				'label' => esc_html__( 'Divider Gap', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'default' => [
					'size' => 10,
				],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 10,
					],
					'rem' => [
						'max' => 10,
					],
				],
				'condition' => [
					'step_type!' => 'progress_bar',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-divider-gap: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'step_progress_bar_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_ACCENT,
				],
				'condition' => [
					'step_type' => 'progress_bar',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-progress-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'step_progress_bar_background_color',
			[
				'label' => esc_html__( 'Background Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_TEXT,
				],
				'condition' => [
					'step_type' => 'progress_bar',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-progress-background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'step_progress_bar_height',
			[
				'label' => esc_html__( 'Height', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vh', 'custom' ],
				'default' => [
					'size' => 20,
				],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 10,
					],
					'rem' => [
						'max' => 10,
					],
				],
				'condition' => [
					'step_type' => 'progress_bar',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-progress-height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'step_progress_bar_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'max' => 100,
					],
					'em' => [
						'max' => 10,
					],
					'rem' => [
						'max' => 10,
					],
				],
				'condition' => [
					'step_type' => 'progress_bar',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-progress-border-radius: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'step_progress_bar_percentage_heading',
			[
				'label' => esc_html__( 'Percentage', 'modulix' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'step_type' => 'progress_bar',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'step_progress_bar_percentage__typography',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_ACCENT,
				],
				'selector' => '{{WRAPPER}} .e-form__indicators__indicator__progress__meter',
				'condition' => [
					'step_type' => 'progress_bar',
				],
			]
		);

		$this->add_control(
			'step_progress_bar_percentage_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_TEXT,
				],
				'condition' => [
					'step_type' => 'progress_bar',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-form-steps-indicator-progress-meter-color: {{VALUE}};',
				],
			]
		);

		// End of steps style.
		$this->end_controls_section();

	}

	private function render_icon_with_fallback( $settings ) {
		$migrated = isset( $settings['__fa4_migrated']['selected_button_icon'] );
		$is_new = empty( $settings['button_icon'] ) && Icons_Manager::is_migration_allowed();

		if ( $is_new || $migrated ) {
			Icons_Manager::render_icon( $settings['selected_button_icon'], [ 'aria-hidden' => 'true' ] );
		} else {
			?><i class="<?php echo esc_attr( $settings['button_icon'] ); ?>" aria-hidden="true"></i><?php
		}
	}

	protected function render() {
		$instance = $this->get_settings_for_display();

		if ( ! Plugin::elementor()->editor->is_edit_mode() ) {
			/**
			 * Elementor form pre render.
			 *
			 * Fires before the form is rendered in the frontend. This hook allows
			 * developers to add functionality before the form is rendered.
			 *
			 * @since 2.4.0
			 *
			 * @param array $instance Current form settings.
			 * @param Form  $this     An instance of the form.
			 */
			do_action( 'elementor-pro/forms/pre_render', $instance, $this );
		}

		$this->add_render_attribute(
			[
				'wrapper' => [
					'class' => [
						'elementor-form-fields-wrapper',
						'elementor-labels-' . $instance['label_position'],
					],
				],
				'submit-group' => [
					'class' => [
						'elementor-field-group',
						'elementor-column',
						'elementor-field-type-submit',
					],
				],
				'button' => [
					'class' => 'elementor-button',
					'type' => 'submit',
				],
				'button-content-wrapper' => [
					'class' => 'elementor-button-content-wrapper',
				],
				'button-icon' => [
					'class' => 'elementor-button-icon',
				],
				'button-text' => [
					'class' => 'elementor-button-text',
				],
			]
		);

		if ( empty( $instance['button_width'] ) ) {
			$instance['button_width'] = '100';
		}

		$this->add_render_attribute( 'submit-group', 'class', 'elementor-col-' . $instance['button_width'] . ' e-form__buttons' );

		if ( ! empty( $instance['button_width_tablet'] ) ) {
			$this->add_render_attribute( 'submit-group', 'class', 'elementor-md-' . $instance['button_width_tablet'] );
		}

		if ( ! empty( $instance['button_width_mobile'] ) ) {
			$this->add_render_attribute( 'submit-group', 'class', 'elementor-sm-' . $instance['button_width_mobile'] );
		}

		if ( ! empty( $instance['button_size'] ) ) {
			$this->add_render_attribute( 'button', 'class', 'elementor-size-' . $instance['button_size'] );
		}

		if ( ! empty( $instance['button_type'] ) ) {
			$this->add_render_attribute( 'button', 'class', 'elementor-button-' . $instance['button_type'] );
		}

		if ( $instance['button_hover_animation'] ) {
			$this->add_render_attribute( 'button', 'class', 'elementor-animation-' . $instance['button_hover_animation'] );
		}

		if ( ! empty( $instance['form_id'] ) ) {
			$this->add_render_attribute( 'form', 'id', $instance['form_id'] );
		}

		if ( ! empty( $instance['form_name'] ) ) {
			$this->add_render_attribute( 'form', 'name', $instance['form_name'] );
			$this->add_render_attribute( 'form', 'aria-label', $instance['form_name'] );
		}

		if ( 'custom' === $instance['form_validation'] ) {
			$this->add_render_attribute( 'form', 'novalidate' );
		}

		if ( ! empty( $instance['button_css_id'] ) ) {
			$this->add_render_attribute( 'button', 'id', $instance['button_css_id'] );
		}

		$referer_title = trim( wp_title( '', false ) );

		if ( ! $referer_title && is_home() ) {
			$referer_title = get_option( 'blogname' );
		}

		?>
		<form class="elementor-form" method="post" <?php $this->print_render_attribute_string( 'form' ); ?>>
			<input type="hidden" name="post_id" value="<?php // PHPCS - the method Utils::get_current_post_id is safe.
				echo Utils::get_current_post_id(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"/>
			<input type="hidden" name="form_id" value="<?php echo esc_attr( $this->get_id() ); ?>"/>
			<input type="hidden" name="referer_title" value="<?php echo esc_attr( $referer_title ); ?>" />

			<?php if ( is_singular() ) {
				// `queried_id` may be different from `post_id` on Single theme builder templates.
				?>
				<input type="hidden" name="queried_id" value="<?php echo get_the_ID(); ?>"/>
			<?php } ?>

			<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
				<?php
				foreach ( $instance['form_fields'] as $item_index => $item ) :
					$item['input_size'] = $instance['input_size'];
					$this->form_fields_render_attributes( $item_index, $instance, $item );

					$field_type = $item['field_type'];

					/**
					 * Render form field.
					 *
					 * Filters the field rendered by Elementor forms.
					 *
					 * @since 1.0.0
					 *
					 * @param array $item       The field value.
					 * @param int   $item_index The field index.
					 * @param Form  $this       An instance of the form.
					 */
					$item = apply_filters( 'elementor_pro/forms/render/item', $item, $item_index, $this );

					/**
					 * Render form field.
					 *
					 * Filters the field rendered by Elementor forms.
					 *
					 * The dynamic portion of the hook name, `$field_type`, refers to the field type.
					 *
					 * @since 1.0.0
					 *
					 * @param array $item       The field value.
					 * @param int   $item_index The field index.
					 * @param Form  $this       An instance of the form.
					 */
					$item = apply_filters( "elementor_pro/forms/render/item/{$field_type}", $item, $item_index, $this );

					$print_label = ! in_array( $item['field_type'], [ 'hidden', 'html', 'step' ], true );
					?>
				<div <?php $this->print_render_attribute_string( 'field-group' . $item_index ); ?>>
					<?php
					if ( $print_label && $item['field_label'] ) {
						?>
							<label <?php $this->print_render_attribute_string( 'label' . $item_index ); ?>>
								<?php // PHPCS - the variable $item['field_label'] is safe.
								echo $item['field_label']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</label>
						<?php
					}

					switch ( $item['field_type'] ) :
						case 'html':
							echo do_shortcode( $item['field_html'] );
							break;
						case 'textarea':
							// PHPCS - the method make_textarea_field is safe.
							echo $this->make_textarea_field( $item, $item_index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							break;

						case 'select':
							// PHPCS - the method make_select_field is safe.
							echo $this->make_select_field( $item, $item_index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							break;

						case 'radio':
						case 'checkbox':
							// PHPCS - the method make_radio_checkbox_field is safe.
							echo $this->make_radio_checkbox_field( $item, $item_index, $item['field_type'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							break;
						case 'text':
						case 'email':
						case 'url':
						case 'password':
						case 'hidden':
						case 'search':
							$this->add_render_attribute( 'input' . $item_index, 'class', 'elementor-field-textual' );
							?>
								<input size="1" <?php $this->print_render_attribute_string( 'input' . $item_index ); ?>>
							<?php
							break;
						default:
							$field_type = $item['field_type'];

							/**
							 * Elementor form field render.
							 *
							 * Fires when a field is rendered in the frontend. This hook allows developers to
							 * add functionality when from fields are rendered.
							 *
							 * The dynamic portion of the hook name, `$field_type`, refers to the field type.
							 *
							 * @since 1.0.0
							 *
							 * @param array $item       The field value.
							 * @param int   $item_index The field index.
							 * @param Form  $this       An instance of the form.
							 */
							do_action( "elementor_pro/forms/render_field/{$field_type}", $item, $item_index, $this );
					endswitch;
					?>
				</div>
				<?php endforeach; ?>
				<div <?php $this->print_render_attribute_string( 'submit-group' ); ?>>
					<button <?php $this->print_render_attribute_string( 'button' ); ?>>
						<span <?php $this->print_render_attribute_string( 'button-content-wrapper' ); ?>>
							<?php if ( ! empty( $instance['button_icon'] ) || ! empty( $instance['selected_button_icon']['value'] ) ) : ?>
								<span <?php $this->print_render_attribute_string( 'button-icon' ); ?>>
									<?php $this->render_icon_with_fallback( $instance ); ?>
									<?php if ( empty( $instance['button_text'] ) ) : ?>
										<span class="elementor-screen-only"><?php echo esc_html__( 'Submit', 'modulix' ); ?></span>
									<?php endif; ?>
								</span>
							<?php endif; ?>
							<?php if ( ! empty( $instance['button_text'] ) ) : ?>
								<span <?php $this->print_render_attribute_string( 'button-text' ); ?>><?php echo wp_kses_post( $instance['button_text'] ); ?></span>
							<?php endif; ?>
						</span>
					</button>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Render Form widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 2.9.0
	 * @access protected
	 */
	protected function content_template() {
		?>
		<#
		view.addRenderAttribute( 'form', 'class', 'elementor-form' );

		if ( '' !== settings.form_id ) {
			view.addRenderAttribute( 'form', 'id', settings.form_id );
		}

		if ( '' !== settings.form_name ) {
			view.addRenderAttribute( 'form', 'name', settings.form_name );
		}

		if ( 'custom' === settings.form_validation ) {
			view.addRenderAttribute( 'form', 'novalidate' );
		}
		#>
		<form {{{ view.getRenderAttributeString( 'form' ) }}}>
			<div class="elementor-form-fields-wrapper elementor-labels-{{settings.label_position}}">
				<#
					for ( var i in settings.form_fields ) {
						var item = settings.form_fields[ i ];
						item = elementor.hooks.applyFilters( 'elementor_pro/forms/content_template/item', item, i, settings );

						item.field_type  = _.escape( item.field_type );
						item.field_value = _.escape( item.field_value );

						var options = item.field_options ? item.field_options.split( '\n' ) : [],
							itemClasses = _.escape( item.css_classes ),
							labelVisibility = '',
							placeholder = '',
							required = '',
							inputField = '',
							multiple = '',
							fieldGroupClasses = 'elementor-field-group elementor-column elementor-field-type-' + item.field_type,
							printLabel = settings.show_labels && ! [ 'hidden', 'html', 'step' ].includes( item.field_type );

						fieldGroupClasses += ' elementor-col-' + ( ( '' !== item.width ) ? item.width : '100' );

						if ( item.width_tablet ) {
							fieldGroupClasses += ' elementor-md-' + item.width_tablet;
						}

						if ( item.width_mobile ) {
							fieldGroupClasses += ' elementor-sm-' + item.width_mobile;
						}

						if ( item.required ) {
							required = 'required';
							fieldGroupClasses += ' elementor-field-required';

							if ( settings.mark_required ) {
								fieldGroupClasses += ' elementor-mark-required';
							}
						}

						if ( item.placeholder ) {
							placeholder = 'placeholder="' + _.escape( item.placeholder ) + '"';
						}

						if ( item.allow_multiple ) {
							multiple = ' multiple';
							fieldGroupClasses += ' elementor-field-type-' + item.field_type + '-multiple';
						}

						switch ( item.field_type ) {
							case 'step':
								inputField = `<div
									class="e-field-step elementor-hidden"
									data-label="${ item.field_label }"
									data-previousButton="${ item.previous_button || '' }"
									data-nextButton="${ item.next_button || '' }"
									data-iconUrl="${ 'svg' === item.selected_icon.library && item.selected_icon.value ? item.selected_icon.value.url : '' }"
									data-iconLibrary="${ 'svg' !== item.selected_icon.library && item.selected_icon.value ? item.selected_icon.value : '' }"></div>`;
								break;
							case 'html':
								inputField = item.field_html;
								break;

							case 'textarea':
								inputField = '<textarea class="elementor-field elementor-field-textual elementor-size-' + settings.input_size + ' ' + itemClasses + '" name="form_field_' + i + '" id="form_field_' + i + '" rows="' + item.rows + '" ' + required + ' ' + placeholder + '>' + item.field_value + '</textarea>';
								break;

							case 'select':
								if ( options ) {
									var size = '';
									if ( item.allow_multiple && item.select_size ) {
										size = ' size="' + item.select_size + '"';
									}
									inputField = '<div class="elementor-field elementor-select-wrapper ' + itemClasses + '">';
									inputField += '<select class="elementor-field-textual elementor-size-' + settings.input_size + '" name="form_field_' + i + '" id="form_field_' + i + '" ' + required + multiple + size + ' >';
									for ( var x in options ) {
										var option_value = options[ x ];
										var option_label = options[ x ];
										var option_id = 'form_field_option' + i + x;

										if ( options[ x ].indexOf( '|' ) > -1 ) {
											var label_value = options[ x ].split( '|' );
											option_label = label_value[0];
											option_value = label_value[1];
										}

										view.addRenderAttribute( option_id, 'value', option_value );
										if ( item.field_value.split( ',' ) .indexOf( option_value ) ) {
											view.addRenderAttribute( option_id, 'selected', 'selected' );
										}
										inputField += '<option ' + view.getRenderAttributeString( option_id ) + '>' + option_label + '</option>';
									}
									inputField += '</select></div>';
								}
								break;

							case 'radio':
							case 'checkbox':
								if ( options ) {
									var multiple = '';

									if ( 'checkbox' === item.field_type && options.length > 1 ) {
										multiple = '[]';
									}

									inputField = '<div class="elementor-field-subgroup ' + itemClasses + ' ' + _.escape( item.inline_list ) + '">';

									for ( var x in options ) {
										var option_value = options[ x ];
										var option_label = options[ x ];
										var option_id = 'form_field_' + item.field_type + i + x;
										if ( options[x].indexOf( '|' ) > -1 ) {
											var label_value = options[x].split( '|' );
											option_label = label_value[0];
											option_value = label_value[1];
										}

										view.addRenderAttribute( option_id, {
											value: option_value,
											type: item.field_type,
											id: 'form_field_' + i + '-' + x,
											name: 'form_field_' + i + multiple
										} );

										if ( option_value ===  item.field_value ) {
											view.addRenderAttribute( option_id, 'checked', 'checked' );
										}

										inputField += '<span class="elementor-field-option"><input ' + view.getRenderAttributeString( option_id ) + ' ' + required + '> ';
										inputField += '<label for="form_field_' + i + '-' + x + '">' + option_label + '</label></span>';

									}

									inputField += '</div>';
								}
								break;

							case 'text':
							case 'email':
							case 'url':
							case 'password':
							case 'number':
							case 'search':
								itemClasses = 'elementor-field-textual ' + itemClasses;
								inputField = '<input size="1" type="' + item.field_type + '" value="' + item.field_value + '" class="elementor-field elementor-size-' + settings.input_size + ' ' + itemClasses + '" name="form_field_' + i + '" id="form_field_' + i + '" ' + required + ' ' + placeholder + ' >';
								break;
							default:
								item.placeholder = _.escape( item.placeholder );
								inputField = elementor.hooks.applyFilters( 'elementor_pro/forms/content_template/field/' + item.field_type, '', item, i, settings );
						}

						if ( inputField ) {
							#>
							<div class="{{ fieldGroupClasses }}">

								<# if ( printLabel && item.field_label ) { #>
									<label class="elementor-field-label" for="form_field_{{ i }}" {{{ labelVisibility }}}>{{{ item.field_label }}}</label>
								<# } #>

								{{{ inputField }}}
							</div>
							<#
						}
					}

					view.addRenderAttribute(
						'submit-group',
						{
							'class': [
								'elementor-field-group',
								'elementor-column',
								'elementor-field-type-submit',
								'e-form__buttons',
								'elementor-col-' + ( ( '' !== settings.button_width ) ? settings.button_width : '100' )
							]
						}
					);

					if ( settings.button_width_tablet ) {
						view.addRenderAttribute( 'submit-group', 'class', 'elementor-md-' + settings.button_width_tablet );
					}

					if ( settings.button_width_mobile ) {
						view.addRenderAttribute( 'submit-group', 'class', 'elementor-sm-' + settings.button_width_mobile );
					}

					view.addRenderAttribute( 'button', 'type', 'submit' );
					view.addRenderAttribute( 'button', 'class', 'elementor-button' );

					if ( '' !== settings.button_css_id ) {
						view.addRenderAttribute( 'button', 'id', settings.button_css_id );
					}

					if ( '' !== settings.button_size ) {
						view.addRenderAttribute( 'button', 'class', 'elementor-size-' + settings.button_size );
					}

					if ( '' !== settings.button_type ) {
						view.addRenderAttribute( 'button', 'class', 'elementor-button-' + settings.button_type );
					}

					if ( '' !== settings.button_hover_animation ) {
						view.addRenderAttribute( 'button', 'class', 'elementor-animation-' + settings.button_hover_animation );
					}

					view.addRenderAttribute( 'button-content-wrapper', 'class', 'elementor-button-content-wrapper' );
					view.addRenderAttribute( 'button-icon', 'class', 'elementor-button-icon' );
					view.addRenderAttribute( 'button-text', 'class', 'elementor-button-text' );

					const iconHTML = elementor.helpers.renderIcon( view, settings.selected_button_icon, { 'aria-hidden': true }, 'i' , 'object' );
					const migrated = elementor.helpers.isIconMigrated( settings, 'selected_button_icon' );
					#>
					<div {{{ view.getRenderAttributeString( 'submit-group' ) }}}>
						<button {{{ view.getRenderAttributeString( 'button' ) }}}>
							<span {{{ view.getRenderAttributeString( 'button-content-wrapper' ) }}}>
								<# if ( settings.button_icon || settings.selected_button_icon ) { #>
									<span {{{ view.getRenderAttributeString( 'button-icon' ) }}}>
										<# if ( iconHTML && iconHTML.rendered && ( ! settings.button_icon || migrated ) ) { #>
											{{{ iconHTML.value }}}
										<# } else { #>
											<i class="{{ settings.button_icon }}" aria-hidden="true"></i>
										<# } #>
										<span class="elementor-screen-only"><?php echo esc_html__( 'Submit', 'modulix' ); ?></span>
									</span>
								<# } #>

								<# if ( settings.button_text ) { #>
									<span {{{ view.getRenderAttributeString( 'button-text' ) }}}>{{ settings.button_text }}</span>
								<# } #>
							</span>
						</button>
					</div>
			</div>
		</form>
		<?php
	}

	public function get_group_name() {
		return 'forms';
	}
}
