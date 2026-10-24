<?php

namespace Modulix\Modules\Search\Widgets;
// Modulix: derived from Elementor Pro 4.0.4, modules/search/widgets/search.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Icons_Manager;
use Elementor\Utils;
use Modulix\Core\Utils\Collection;
use Modulix\Core\Utils as Pro_Utils;
use Modulix\Modules\QueryControl\Controls\Group_Control_Query;
use Modulix\Modules\QueryControl\Module as Module_Query;
use Modulix\Plugin;
use Modulix\Base\Base_Widget;
use Modulix\Modules\QueryControl\Controls\Template_Query;
use Modulix\Modules\QueryControl\Module as QueryControlModule;
use Elementor\Core\Base\Document;
use Modulix\Modules\LoopBuilder\Documents\Loop as LoopDocument;
use Modulix\Modules\LoopBuilder\Files\Css\Loop_Css_Trait;

class Search extends Base_Widget {

	use Loop_Css_Trait;

	protected $query = null;

	protected $search_term = '';
	protected $page_number = 1;

	private $element_attribute_ids = [
		'search_wrapper' => 'search_wrapper',
		'form' => 'form',
		'label' => 'label',
		'label_text' => 'label_text',
		'input' => 'input',
		'input_wrapper' => 'input_wrapper',
		'submit_button' => 'submit_button',
		'submit_text' => 'submit_text',
		'results_wrapper' => 'results_wrapper',
		'widget_props' => 'widget_props',
		'pagination' => 'pagination',
		'nav' => 'nav',
	];

	public function set_search_term( string $search_term ) {
		$this->search_term = $search_term;
	}

	public function set_page_number( int $page_number ) {
		$this->page_number = $page_number;
	}

	public function get_name() {
		return 'search';
	}

	public function get_title() {
		return esc_html__( 'Search', 'modulix' );
	}

	public function get_icon() {
		return 'eicon-site-search';
	}

	public function get_keywords() {
		return [ 'search' ];
	}

	public function get_categories() {
		return [ 'pro-elements' ];
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
		return [ 'widget-search' ];
	}

	public function get_query() {
		if ( null === $this->query ) {
			$this->query_posts();
		}

		return $this->query;
	}

	public function get_query_args() {
		/** @var Module_Query $elementor_query */
		$elementor_query = Module_Query::instance();
		$settings = $this->get_settings_for_display();
		$query_args = [
			'posts_per_page' => $settings['number_of_items'] ?? -1,
			'paged' => $this->page_number,
		];

		if ( ! empty( $this->search_term ) ) {
			$query_args['s'] = $this->search_term;
		}

		return $elementor_query->get_query( $this, $this->get_property_key_prefix(), $query_args, [] );
	}

	protected function register_controls() {
		$this->register_content_tab();
		$this->register_style_tab();
	}

	protected function register_content_tab() {
		$this->register_content_section_search_field();
		$this->register_content_section_results();
		$this->register_content_section_query();
		$this->register_content_section_additional_settings();
	}

	protected function register_content_section_search_field() {
		$this->start_controls_section(
			'content_section_search_field',
			[
				'label' => esc_html__( 'Search Field', 'modulix' ),
			]
		);

		$this->add_control(
			'heading_input',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Input', 'modulix' ),
			]
		);

		$this->add_control(
			'search_input_placeholder_text',
			[
				'label' => esc_html__( 'Placeholder', 'modulix' ),
				'label_block' => true,
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Type to start searching...', 'modulix' ),
				'dynamic' => [
					'active' => true,
				],
				'ai' => [
					'active' => false,
				],
			]
		);

		$this->add_control(
			'icon_search',
			[
				'label' => esc_html__( 'Icon', 'modulix' ),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin' => 'inline',
				'label_block' => false,
			]
		);

		$this->add_control(
			'autocomplete',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Autocomplete', 'modulix' ),
				'label_on' => esc_html__( 'Show', 'modulix' ),
				'label_off' => esc_html__( 'Hide', 'modulix' ),
				'separator' => 'before',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'heading_clear',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Clear', 'modulix' ),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'icon_clear',
			[
				'label' => esc_html__( 'Icon', 'modulix' ),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin' => 'inline',
				'skin_settings' => [
					'inline' => [
						'icon' => [
							'icon' => 'eicon-close',
						],
					],
				],
				'label_block' => false,
				'default' => [
					'value' => 'fas fa-times',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'heading_submit',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Submit', 'modulix' ),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'submit_trigger',
			[
				'label' => esc_html__( 'Trigger', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'click_submit' => esc_html__( 'Submit button', 'modulix' ),
					'key_enter' => esc_html__( 'Enter key', 'modulix' ),
					'both' => esc_html__( 'Both', 'modulix' ),
				],
				'default' => 'click_submit',
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'submit_button_text',
			[
				'label' => esc_html__( 'Text', 'modulix' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Search', 'modulix' ),
				'placeholder' => esc_html__( 'Search', 'modulix' ),
				'condition' => [
					'submit_trigger!' => 'key_enter',
				],
				'dynamic' => [
					'active' => true,
				],
				'ai' => [
					'active' => false,
				],
			]
		);

		$this->add_control(
			'icon_submit',
			[
				'label' => esc_html__( 'Icon', 'modulix' ),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin' => 'inline',
				'label_block' => false,
				'condition' => [
					'submit_trigger!' => 'key_enter',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_content_section_results() {
		$this->start_controls_section(
			'content_section_results',
			[
				'label' => esc_html__( 'Results', 'modulix' ),
			]
		);

		$this->add_control(
			'live_results',
			[
				'label' => esc_html__( 'Live Results', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Hide', 'modulix' ),
				'label_on' => esc_html__( 'Show', 'modulix' ),
				'default' => '',
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'template_id',
			[
				'label' => esc_html__( 'Choose a template', 'modulix' ),
				'type' => Template_Query::CONTROL_ID,
				'label_block' => true,
				'autocomplete' => [
					'object' => QueryControlModule::QUERY_OBJECT_LIBRARY_TEMPLATE,
					'query' => [
						'post_status' => Document::STATUS_PUBLISH,
						'meta_query' => [
							[
								'key' => Document::TYPE_META_KEY,
								'value' => LoopDocument::get_type(),
								'compare' => 'IN',
							],
						],
					],
				],
				'actions' => [
					'new' => [
						'visible' => true,
						'document_config' => [
							'type' => LoopDocument::get_type(),
						],
						'after_action' => 'redirect',
					],
					'edit' => [
						'visible' => true,
						'after_action' => 'redirect',
					],
				],
				'condition' => [
					'live_results' => 'yes',
				],
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'minimum_search_characters',
			[
				'label' => esc_html__( 'Minimum search characters', 'modulix' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 3,
				'min' => 1,
				'condition' => [
					'live_results' => 'yes',
					'template_id!' => '',
				],
				'frontend_available' => true,
			]
		);

		$this->add_responsive_control(
			'number_of_columns',
			[
				'label' => esc_html__( 'Columns', 'modulix' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 1,
				'min' => 1,
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-results-columns: {{VALUE}}',
				],
				'condition' => [
					'live_results' => 'yes',
					'template_id!' => '',
				],
			]
		);

		$this->add_responsive_control(
			'number_of_items',
			[
				'label' => esc_html__( 'Items', 'modulix' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 6,
				'min' => 1,
				'condition' => [
					'live_results' => 'yes',
					'template_id!' => '',
				],
			]
		);

		$this->add_control(
			'equal_height',
			[
				'label' => esc_html__( 'Equal Height', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Off', 'modulix' ),
				'label_on' => esc_html__( 'On', 'modulix' ),
				'default' => '',
				'condition' => [
					'live_results' => 'yes',
					'template_id!' => '',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-results-grid-auto-rows: 1fr; --e-search-loop-item-equal-height: 100%',
				],
			]
		);

		$this->add_control(
			'enable_loader',
			[
				'label' => esc_html__( 'Loader', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Hide', 'modulix' ),
				'label_on' => esc_html__( 'Show', 'modulix' ),
				'condition' => [
					'live_results' => 'yes',
					'template_id!' => '',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'enable_nothing_found_message',
			[
				'label' => esc_html__( 'Nothing Found Message', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Hide', 'modulix' ),
				'label_on' => esc_html__( 'Show', 'modulix' ),
				'default' => 'yes',
				'condition' => [
					'live_results' => 'yes',
					'template_id!' => '',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'nothing_found_message_description',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'This appears when the search yields no results.', 'modulix' ),
				'content_classes' => 'elementor-descriptor',
				'condition' => $this->get_nothing_found_conditions(),
			]
		);

		$this->add_control(
			'nothing_found_message_text',
			[
				'type' => Controls_Manager::TEXTAREA,
				'dynamic' => [
					'active' => true,
				],
				'condition' => $this->get_nothing_found_conditions(),
				'default' => esc_html__( 'It seems we can’t find what you’re looking for.', 'modulix' ),
			]
		);

		$this->add_control(
			'nothing_found_message_html_tag',
			[
				'label' => esc_html__( 'HTML Tag', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
					'div' => 'div',
					'span' => 'span',
				],
				'default' => 'div',
				'condition' => $this->get_nothing_found_conditions(),
			]
		);

		$this->end_controls_section();
	}

	protected function register_content_section_query() {
		$this->start_controls_section(
			'content_section_query',
			[
				'label' => esc_html__( 'Query', 'modulix' ),
			]
		);

		$this->add_group_control(
			Group_Control_Query::get_type(),
			[
				'name' => 'search_query',
				'presets' => [ 'full' ],
				'fields_options' => [
					'post_type' => [
						'default' => 'any',
						'options' => $this->get_supported_post_types(),
					],
					'exclude' => [
						'options' => [
							'terms' => esc_html__( 'Term', 'modulix' ),
							'authors' => esc_html__( 'Author', 'modulix' ),
							'manual_selection' => esc_html__( 'Manual Selection', 'modulix' ),
						],
					],
				],
				'exclude' => [
					'avoid_duplicates',
					'offset',
					'posts_per_page',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_content_section_additional_settings() {
		$this->start_controls_section(
			'content_section_additional_settings',
			[
				'label' => esc_html__( 'Additional Settings', 'modulix' ),
			]
		);

		$this->add_control(
			'heading_pagination',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Pagination', 'modulix' ),
			]
		);

		$this->add_control(
			'pagination_type_options',
			[
				'label' => esc_html__( 'Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'none' => esc_html__( 'None', 'modulix' ),
					'numbers' => esc_html__( 'Numbers', 'modulix' ),
					'previous_next' => esc_html__( 'Previous/Next', 'modulix' ),
					'numbers_previous_next' => esc_html__( 'Numbers + Previous/Next', 'modulix' ),
				],
				'default' => 'none',
				'frontend_available' => true,
			]
		);

		$this->add_control(
			'pagination_prev_label',
			[
				'label' => esc_html__( 'Previous Label', 'modulix' ),
				'dynamic' => [
					'active' => true,
				],
				'default' => esc_html__( 'Previous', 'modulix' ),
				'condition' => [
					'pagination_type_options' => [
						'previous_next',
						'numbers_previous_next',
					],
				],
			]
		);

		$this->add_control(
			'pagination_next_label',
			[
				'label' => esc_html__( 'Next Label', 'modulix' ),
				'default' => esc_html__( 'Next', 'modulix' ),
				'condition' => [
					'pagination_type_options' => [
						'previous_next',
						'numbers_previous_next',
					],
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'page_limit_settings',
			[
				'label' => esc_html__( 'Page Limit', 'modulix' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 5,
				'frontend_available' => true,
				'condition' => [
					'pagination_type_options' => [
						'numbers_previous_next',
						'numbers',
						'previous_next',
					],
				],
			]
		);

		$this->add_control(
			'pagination_shorten_settings',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Shorten', 'modulix' ),
				'label_on' => esc_html__( 'Yes', 'modulix' ),
				'label_off' => esc_html__( 'No', 'modulix' ),
				'return_value' => 'yes',
				'condition' => [
					'pagination_type_options' => [
						'numbers_previous_next',
						'numbers',
					],
				],
			]
		);

		$this->end_controls_section();
	}
	protected function get_nothing_found_conditions() {
		return [
			'enable_nothing_found_message' => 'yes',
			'live_results' => 'yes',
			'template_id!' => '',
		];
	}

	protected function register_style_tab() {
		$this->register_style_section_search_field();
		$this->register_style_section_clear();
		$this->register_style_section_submit();
		$this->register_style_section_results();
		$this->register_style_section_additional_settings();
		$this->register_style_section_nothing_found_message();
	}

	protected function register_style_section_search_field() {
		$this->start_controls_section(
			'section_search_field_style',
			[
				'label' => esc_html__( 'Search Field', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'search_field_typography',
				'selector' => '{{WRAPPER}} .e-search-input',
			]
		);

		$this->add_control(
			'placeholder_color',
			[
				'label' => esc_html__( 'Placeholder Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-placeholder-color: {{VALUE}};',
				],
			]
		);

		$this->start_controls_tabs( 'search_field_tabs' );

		$this->start_controls_tab(
			'search_field_tab_input_normal',
			[
				'label' => esc_html__( 'Normal', 'modulix' ),
			]
		);

		$this->register_search_field_style_tabs( 'normal' );

		$this->end_controls_tab();

		$this->start_controls_tab(
			'search_field_tab_input_focus',
			[
				'label' => esc_html__( 'Focus', 'modulix' ),
			]
		);

		$this->register_search_field_style_tabs( 'focus' );

		$this->add_control(
			'search_field_search_input_transition',
			[
				'label' => esc_html__( 'Transition Duration', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 's', 'ms', 'custom' ],
				'default' => [
					'unit' => 's',
				],
				'range' => [
					's' => [
						'max' => 5,
					],
					'ms' => [
						'max' => 5000,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-input-transition: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'search_field_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}} .e-search-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'search_field_padding',
			[
				'label' => esc_html__( 'Padding', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-input-padding-block-start: {{TOP}}{{UNIT}}; --e-search-input-padding-inline-start: {{LEFT}}{{UNIT}}; --e-search-input-padding-block-end: {{BOTTOM}}{{UNIT}}; --e-search-input-padding-inline-end: {{RIGHT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_icon_label_size',
			[
				'label' => esc_html__( 'Icon Size', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vw' ],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-icon-label-size: {{SIZE}}{{UNIT}}',
				],
				'separator' => 'before',
				'condition' => [
					'icon_search[value]!' => '',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_icon_gap',
			[
				'label' => esc_html__( 'Gap between text and icon', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vw' ],
				'range' => [
					'px' => [
						'max' => 50,
					],
					'em' => [
						'max' => 5,
					],
					'rem' => [
						'max' => 5,
					],
					'vw' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-input-gap: {{SIZE}}{{UNIT}}',
				],
				'condition' => [
					'icon_search[value]!' => '',
				],
			]
		);

		$this->add_responsive_control(
			'search_field_submit_gap',
			[
				'label' => esc_html__( 'Gap between input and button', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vw' ],
				'range' => [
					'px' => [
						'max' => 50,
					],
					'em' => [
						'max' => 5,
					],
					'rem' => [
						'max' => 5,
					],
					'vw' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-submit-margin-inline-start: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_search_field_style_tabs( $tab_id ) {
		$is_normal = 'normal' === $tab_id;
		$selector_suffix = $is_normal ? '' : '.e-focus';

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => "search_field_background_$tab_id",
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => "{{WRAPPER}}$selector_suffix .e-search-input",
				'fields_options' => [
					'color' => [
						'label' => esc_html__( 'Background', 'modulix' ),
					],
				],
			]
		);

		$this->add_control(
			"search_field_input_text_color_$tab_id",
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_TEXT,
				],
				'selectors' => [
					"{{WRAPPER}}$selector_suffix" => '--e-search-input-color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			"search_field_icon_search_normal_color_$tab_id",
			[
				'label' => esc_html__( 'Icon Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					"{{WRAPPER}}$selector_suffix" => '--e-search-icon-label-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => "search_field_border_$tab_id",
				'selector' => "{{WRAPPER}}$selector_suffix .e-search-input",
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => "search_field_box_shadow__$tab_id",
				'selector' => "{{WRAPPER}}$selector_suffix .e-search-input",
			]
		);
	}

	protected function register_style_section_clear() {
		$this->start_controls_section(
			'style_section_clear',
			[
				'label' => esc_html__( 'Clear', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'icon_clear[value]!' => '',
				],
			]
		);

		$this->add_responsive_control(
			'icon_clear_size',
			[
				'label' => esc_html__( 'Icon Size', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 50,
					],
					'em' => [
						'max' => 5,
					],
					'rem' => [
						'max' => 5,
					],
					'vw' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-icon-clear-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'icon_clear_color_tabs',
		);

		$this->start_controls_tab(
			'icon_clear_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'modulix' ),
			]
		);

		$this->add_control(
			'icon_clear_normal_color',
			[
				'label' => esc_html__( 'Icon Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-icon-clear-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'icon_clear_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'modulix' ),
			]
		);

		$this->add_control(
			'icon_clear_hover_color',
			[
				'label' => esc_html__( 'Icon Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .e-search-input-wrapper > svg:hover' => '--e-search-icon-clear-color: {{VALUE}};',
					'{{WRAPPER}} .e-search-input-wrapper > i:hover' => '--e-search-icon-clear-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'icon_clear_hover_transition',
			[
				'label' => esc_html__( 'Transition Duration', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 's', 'ms', 'custom' ],
				'default' => [
					'unit' => 's',
				],
				'range' => [
					's' => [
						'max' => 5,
					],
					'ms' => [
						'max' => 5000,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-icon-clear-transition: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function register_style_section_additional_settings() {
		$this->start_controls_section(
			'style_additional_settings',
			[
				'label' => esc_html__( 'Additional Settings', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'pagination_type_options' => [
						'numbers_previous_next',
						'previous_next',
						'numbers',
					],
				],
			]
		);

		$this->add_control(
			'heading_style_additional_settings',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Pagination', 'modulix' ),
			]
		);

		$this->add_control(
			'style_additional_settings_alignment',
			[
				'label' => esc_html__( 'Alignment', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [
						'title' => esc_html__( 'Start', 'modulix' ),
						'icon' => 'eicon-h-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Middle', 'modulix' ),
						'icon' => 'eicon-h-align-center',
					],
					'flex-end' => [
						'title' => esc_html__( 'End', 'modulix' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'toggle' => true,
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-pagination-justify-content: {{VALUE}};',
				],

			]
		);

		$this->add_control(
			'style_additional_settings_vertical_position',
			[
				'label' => esc_html__( 'Vertical Position', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'top' => [
						'title' => esc_html__( 'Top', 'modulix' ),
						'icon' => 'eicon-v-align-top',
					],
					'bottom' => [
						'title' => esc_html__( 'Bottom', 'modulix' ),
						'icon' => 'eicon-v-align-bottom',
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'top' => '--e-search-pagination-vertical-position: column-reverse',
					'bottom' => '--e-search-pagination-vertical-position: column',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'typography_title',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],
				'selector' => '{{WRAPPER}} .elementor-pagination',
			]
		);

		$this->add_control(
			'heading_style_additional_settings_colors',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Colors', 'modulix' ),
				'separator' => 'before',
			]
		);

		$this->start_controls_tabs(
			'style_additional_settings_color_tabs',
		);

		$this->start_controls_tab(
			'style_additional_settings_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'modulix' ),
			]
		);

		$this->add_control(
			'style_additional_settings_normal_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-pagination-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'style_additional_settings_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'modulix' ),
			]
		);

		$this->add_control(
			'style_additional_settings_hover_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-pagination-hover: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'style_button_color_tabs_active',
			[
				'label' => esc_html__( 'Active', 'modulix' ),
			]
		);

		$this->add_control(
			'style_button_color_icon_active',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-pagination-current: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'page_numbers_space_between',
			[
				'label' => esc_html__( 'Space Between', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-pagination-page-numbers-gap: {{SIZE}}{{UNIT}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'top_spacing',
			[
				'label' => esc_html__( 'Top Spacing', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'max' => 200,
					],
					'em' => [
						'max' => 20,
					],
					'rem' => [
						'max' => 20,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-pagination-block-start-spacing: {{SIZE}}{{UNIT}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'bottom_spacing',
			[
				'label' => esc_html__( 'Bottom Spacing', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'max' => 200,
					],
					'em' => [
						'max' => 20,
					],
					'rem' => [
						'max' => 20,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-pagination-block-end-spacing: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_style_section_submit() {
		$this->start_controls_section(
			'style_section_submit',
			[
				'label' => esc_html__( 'Submit Button', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'submit_trigger!' => 'key_enter',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'submit_typography',
				'selector' => '{{WRAPPER}} .e-search-submit span',
			]
		);

		$this->add_responsive_control(
			'icon_submit_position',
			[
				'label' => esc_html__( 'Icon Position', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'start' => [
						'title' => esc_html__( 'Start (before)', 'modulix' ),
						'icon' => 'eicon-h-align-left',
					],
					'end' => [
						'title' => esc_html__( 'End (after)', 'modulix' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'condition' => [
					'icon_submit[value]!' => '',
				],
				'selectors' => [
					'{{WRAPPER}}' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'start' => '--e-search-submit-button-flex-direction: row; --e-search-submit-icon-margin-inline-start: 0px; --e-search-submit-icon-margin-inline-end: var(--e-search-submit-icon-gap);',
					'end' => '--e-search-submit-button-flex-direction: row-reverse; --e-search-submit-icon-margin-inline-start: var(--e-search-submit-icon-gap); --e-search-submit-icon-margin-inline-end: 0px;',
				],
			]
		);

		$this->add_responsive_control(
			'icon_submit_size',
			[
				'label' => esc_html__( 'Icon Size', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 50,
					],
					'em' => [
						'max' => 5,
					],
					'rem' => [
						'max' => 5,
					],
					'vw' => [
						'max' => 50,
					],
				],
				'condition' => [
					'icon_submit[value]!' => '',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-icon-submit-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_submit_gap',
			[
				'label' => esc_html__( 'Gap between text and icon', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 50,
					],
					'em' => [
						'max' => 5,
					],
					'rem' => [
						'max' => 5,
					],
					'vw' => [
						'max' => 50,
					],
				],
				'condition' => [
					'icon_submit[value]!' => '',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-submit-icon-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'submit_style_tabs',
		);

		$this->start_controls_tab(
			'submit_style_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'modulix' ),
			]
		);

		$this->register_submit_style_tabs( 'normal' );

		$this->end_controls_tab();

		$this->start_controls_tab(
			'submit_style_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'modulix' ),
			]
		);

		$this->register_submit_style_tabs( 'hover' );

		$this->add_control(
			'submit_hover_animation',
			[
				'label' => esc_html__( 'Hover animation', 'modulix' ),
				'type' => Controls_Manager::HOVER_ANIMATION,
			]
		);

		$this->add_control(
			'submit_hover_transition',
			[
				'label' => esc_html__( 'Transition Duration', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 's', 'ms', 'custom' ],
				'default' => [
					'unit' => 's',
				],
				'range' => [
					's' => [
						'max' => 5,
					],
					'ms' => [
						'max' => 5000,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-submit-hover-transition: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'submit_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-submit-border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'submit_padding',
			[
				'label' => esc_html__( 'Padding', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-submit-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Registers the controls of the submit section style for normal/hover state tabs.
	 *
	 * @param string $tab_id Accepts 'normal' or 'hover' as value.
	 */
	protected function register_submit_style_tabs( $tab_id ) {
		$is_normal = 'normal' === $tab_id;
		$state_suffix = $is_normal ? '' : ":$tab_id";
		$selector_suffix = $is_normal ? '' : " .e-search-submit$state_suffix";

		$this->add_control(
			"submit_text_color_$tab_id",
			[
				'label' => esc_html__( 'Text Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					"{{WRAPPER}}$selector_suffix" => '--e-search-submit-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			"submit_icon_color_$tab_id",
			[
				'label' => esc_html__( 'Icon Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'condition' => [
					'icon_submit[value]!' => '',
				],
				'selectors' => [
					"{{WRAPPER}}$selector_suffix" => '--e-search-icon-submit-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => "submit_background_$tab_id",
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => "{{WRAPPER}} .e-search-submit$state_suffix",
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => "submit_border_$tab_id",
				'selector' => "{{WRAPPER}} .e-search-submit$state_suffix",
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => "submit_box_shadow_$tab_id",
				'selector' => "{{WRAPPER}} .e-search-submit$state_suffix",
			]
		);
	}

	protected function register_style_section_results() {
		$this->start_controls_section(
			'style_section_results',
			[
				'label' => esc_html__( 'Results', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'live_results' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'results_background',
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .e-search-results-container',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'results_border_type',
				'selector' => '{{WRAPPER}} .e-search-results-container > div',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'results_box_shadow',
				'selector' => '{{WRAPPER}} .e-search-results-container > div',
			]
		);

		$this->add_responsive_control(
			'results_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-results-border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'results_padding',
			[
				'label' => esc_html__( 'Padding', 'modulix' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-results-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'results_distance_from_search_field',
			[
				'label' => esc_html__( 'Distance from search field', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 50,
					],
					'em' => [
						'max' => 5,
					],
					'rem' => [
						'max' => 5,
					],
					'vw' => [
						'max' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-input-and-results-gap: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'results_is_dropdown_width',
			[
				'label' => esc_html__( 'Dropdown Width', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'search_field' => esc_html__( 'Search Field', 'modulix' ),
					'widget_width' => esc_html__( 'Widget Width', 'modulix' ),
				],
				'default' => 'search_field',
				'separator' => 'before',
				'frontend_available' => true,
			],
		);

		$this->add_control(
			'results_is_custom_width',
			[
				'label' => esc_html__( 'Custom Width', 'modulix' ),
				'type' => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'On', 'modulix' ),
				'label_on' => esc_html__( 'Off', 'modulix' ),
				'default' => '',
			]
		);

		$this->add_responsive_control(
			'results_custom_width',
			[
				'label' => esc_html__( 'Width', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 1000,
					],
					'em' => [
						'max' => 100,
					],
					'rem' => [
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-results-width: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'results_is_custom_width' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'results_max_height',
			[
				'label' => esc_html__( 'Max height', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'range' => [
					'px' => [
						'max' => 500,
					],
					'%' => [
						'max' => 50,
					],
					'em' => [
						'max' => 50,
					],
					'rem' => [
						'max' => 50,
					],
					'vw' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-results-max-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'results_alignment',
			[
				'label' => esc_html__( 'Alignment', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'start' => [
						'title' => esc_html__( 'Start', 'modulix' ),
						'icon' => 'eicon-h-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Middle', 'modulix' ),
						'icon' => 'eicon-h-align-center',
					],
					'end' => [
						'title' => esc_html__( 'End', 'modulix' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'toggle' => true,
				'condition' => [
					'results_is_custom_width' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}}' => '{{VALUE}}',
				],
				'selectors_dictionary' => [
					'start' => '--e-search-results-inset-inline-start: 0; --e-search-results-inset-inline-end: initial; --e-search-results-transform: initial;',
					'center' => '--e-search-results-inset-inline-start: 50%; --e-search-results-inset-inline-end: initial; --e-search-results-transform: translateX(-50%);',
					'end' => '--e-search-results-inset-inline-start: initial; --e-search-results-inset-inline-end: 0; --e-search-results-transform: initial;',
				],
			]
		);

		$this->add_responsive_control(
			'search_result_column_gap',
			[
				'label' => esc_html__( 'Gap between columns', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-results-column-gap: {{SIZE}}{{UNIT}}',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'search_result_row_gap',
			[
				'label' => esc_html__( 'Gap between rows', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-results-row-gap: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_control(
			'heading_search_reasult_loader',
			[
				'type' => Controls_Manager::HEADING,
				'label' => esc_html__( 'Loader', 'modulix' ),
				'separator' => 'before',
				'condition' => [
					'live_results' => 'yes',
					'template_id!' => '',
					'enable_loader' => 'yes',
				],
			]
		);

		$this->add_control(
			'search_result_loader_icon_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'condition' => [
					'live_results' => 'yes',
					'template_id!' => '',
					'enable_loader' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-loader-icon-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'search_result_loader_icon_size',
			[
				'label' => esc_html__( 'Icon Size', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vw', 'custom' ],
				'condition' => [
					'live_results' => 'yes',
					'template_id!' => '',
					'enable_loader' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-loader-icon-size: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_style_section_nothing_found_message() {
		$this->start_controls_section(
			'style_section_nothing_found_message',
			[
				'label' => esc_html__( 'Nothing Found Message', 'modulix' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => $this->get_nothing_found_conditions(),
			]
		);

		$this->add_responsive_control(
			'nothing_found_message_space_from_top',
			[
				'label' => esc_html__( 'Space from top', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'max' => 200,
					],
					'em' => [
						'max' => 20,
					],
					'rem' => [
						'max' => 20,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-nothing-found-padding-block-start: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'nothing_found_message_space_from_bottom',
			[
				'label' => esc_html__( 'Space from bottom', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
				'range' => [
					'px' => [
						'max' => 200,
					],
					'em' => [
						'max' => 20,
					],
					'rem' => [
						'max' => 20,
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-nothing-found-padding-block-end: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'nothing_found_message_alignment',
			[
				'label' => esc_html__( 'Alignment', 'modulix' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'start' => [
						'title' => esc_html__( 'Start', 'modulix' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Middle', 'modulix' ),
						'icon' => 'eicon-text-align-center',
					],
					'end' => [
						'title' => esc_html__( 'End', 'modulix' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-nothing-found-message-alignment: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'nothing_found_message_typography',
				'selector' => '{{WRAPPER}} .e-search-nothing-found-message',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'nothing_found_message_color',
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}' => '--e-search-nothing-found-message-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'nothing_found_message_text_shadow',
				'selector' => '{{WRAPPER}} .e-search-nothing-found-message',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name' => 'nothing_found_message_text_stroke',
				'selector' => '{{WRAPPER}} .e-search-nothing-found-message',
			]
		);

		$this->end_controls_section();
	}

	public function render_results() {
		$this->query_posts();

		/** @var \WP_Query $query */
		$query = $this->get_query();

		if ( ! $query->found_posts ) {
			$this->handle_no_posts_found();
			return;
		}

		while ( $query->have_posts() ) {
			$query->the_post();

			$this->render_post();
		}

		wp_reset_postdata();
	}

	public function render_pagination() {
		$parent_settings = $this->get_settings_for_display();
		$pagination_types = $parent_settings['pagination_type_options'];
		$this->add_render_attibutes_pagination();

		if ( 'none' === $pagination_types ) {
			return;
		}

		if ( 1 >= $this->get_query()->max_num_pages ) {
			return;
		}

		$total = $parent_settings['page_limit_settings'] > $this->get_query()->max_num_pages ? $this->get_query()->max_num_pages : $parent_settings['page_limit_settings'];

		$paginate_args = [
			'type' => 'array',
			'current' => $this->page_number,
			'total' => $total,
			'prev_next' => 'numbers' !== $pagination_types,
			'next_text' => $parent_settings['pagination_next_label'],
			'prev_text' => $parent_settings['pagination_prev_label'],
			'show_all' => 'yes' !== $parent_settings['pagination_shorten_settings'],
			'before_page_number' => '<span class="elementor-screen-only">' . esc_html__( 'Page', 'modulix' ) . '</span>',
		];

		$paginate_args = $this->get_paginate_args_for_rest_request( $paginate_args );

		$links = [];
		$links = paginate_links( $paginate_args );

		if ( 1 === $this->page_number && 'numbers' !== $pagination_types ) {
			$prev = '<span class="prev page-numbers inactive">' . $parent_settings['pagination_prev_label'] . '</span>';
			array_unshift( $links, $prev );
		}

		if ( $this->page_number === $total && 'numbers' !== $pagination_types ) {
			$next = '<span class="next page-numbers inactive">' . $parent_settings['pagination_next_label'] . '</span>';
			$links[] = $next;
		}

		$links = $this->add_nofollow_to_links( $links );

		echo implode( PHP_EOL, $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	protected function add_nofollow_to_links( $content ) {
		$content = str_replace( '<a ', '<a rel="nofollow" ', $content );
		return $content;
	}

	protected function get_paginate_args_for_rest_request( $paginate_args ) {

		$link_unescaped = wp_get_referer();
		$url_components = wp_parse_url( $link_unescaped );
		$add_args = [];

		if ( isset( $url_components['query'] ) ) {
			wp_parse_str( $url_components['query'], $add_args );
		}

		$url_to_post_id = url_to_postid( $link_unescaped );
		$pagination_base_url = get_permalink( $url_to_post_id );

		$paginate_args['base'] = trailingslashit( $pagination_base_url ) . '%_%';
		$paginate_args['format'] = '?e-search-page=%#%';
		$paginate_args['add_args'] = $add_args;

		if ( 0 === $url_to_post_id ) {
			unset( $paginate_args['format'] );
		}

		return $paginate_args;
	}

	protected function handle_no_posts_found() {
		$settings = $this->get_settings_for_display();

		if ( 'yes' !== $settings['enable_nothing_found_message'] ) {
			return;
		}
		$nothing_found_message_html_tag = Utils::validate_html_tag( $settings['nothing_found_message_html_tag'] );
		?>
			<<?php Utils::print_validated_html_tag( $nothing_found_message_html_tag ); ?> class="e-search-nothing-found-message">
				<?php echo wp_kses_post( $settings['nothing_found_message_text'] ); ?>
			</<?php Utils::print_validated_html_tag( $nothing_found_message_html_tag ); ?>>
		<?php
	}

	protected function render_post() {
		$settings = $this->get_settings_for_display();
		$template_id = $settings['template_id'] ?? 0;
		$post_id = get_the_ID();

		/** @var LoopDocument $document */
		$document = Plugin::elementor()->documents->get( $template_id );

		if ( ! $document ) {
			return;
		}

		$this->print_dynamic_css( $post_id, $template_id );
		$document->print_content();
	}

	protected function render_loader( $settings ) {
		if ( isset( $settings['enable_loader'] ) && 'yes' === $settings['enable_loader'] ) {
			?>
			<div class="e-search-loader">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28">
					<path fill-rule="evenodd" d="M14 .188c.587 0 1.063.475 1.063 1.062V5.5a1.063 1.063 0 0 1-2.126 0V1.25c0-.587.476-1.063 1.063-1.063ZM4.182 4.181a1.063 1.063 0 0 1 1.503 0L8.73 7.228A1.062 1.062 0 1 1 7.228 8.73L4.182 5.685a1.063 1.063 0 0 1 0-1.503Zm19.636 0a1.063 1.063 0 0 1 0 1.503L20.772 8.73a1.062 1.062 0 1 1-1.502-1.502l3.045-3.046a1.063 1.063 0 0 1 1.503 0ZM.188 14c0-.587.475-1.063 1.062-1.063H5.5a1.063 1.063 0 0 1 0 2.126H1.25A1.063 1.063 0 0 1 .187 14Zm21.25 0c0-.587.475-1.063 1.062-1.063h4.25a1.063 1.063 0 0 1 0 2.126H22.5A1.063 1.063 0 0 1 21.437 14ZM8.73 19.27a1.062 1.062 0 0 1 0 1.502l-3.045 3.046a1.063 1.063 0 0 1-1.503-1.503l3.046-3.046a1.063 1.063 0 0 1 1.502 0Zm10.54 0a1.063 1.063 0 0 1 1.502 0l3.046 3.045a1.063 1.063 0 0 1-1.503 1.503l-3.046-3.046a1.063 1.063 0 0 1 0-1.502ZM14 21.438c.587 0 1.063.475 1.063 1.062v4.25a1.063 1.063 0 0 1-2.126 0V22.5c0-.587.476-1.063 1.063-1.063Z"/>
				</svg>
			</div>
			<?php
		}
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$attribute_ids = $this->element_attribute_ids;
		$this->add_render_attibutes();
		?>
		<search <?php $this->print_render_attribute_string( $attribute_ids['search_wrapper'] ); ?>>
			<form <?php $this->print_render_attribute_string( $attribute_ids['form'] ); ?>>

				<?php do_action( 'elementor_pro/search/before_input', $this ); ?>

				<label <?php $this->print_render_attribute_string( $attribute_ids['label'] ); ?>>
					<span <?php $this->print_render_attribute_string( $attribute_ids['label_text'] ); ?>>
						<?php echo esc_html__( 'Search', 'modulix' ); ?>
					</span>
					<?php $this->maybe_render_icon( 'icon_search' ); ?>
				</label>

				<div <?php $this->print_render_attribute_string( $attribute_ids['input_wrapper'] ); ?>>
					<input <?php $this->print_render_attribute_string( $attribute_ids['input'] ); ?>>
					<?php $this->maybe_render_icon( 'icon_clear' ); ?>
					<?php if ( ! $this->get_dropdown_width() ) : ?>
					<output <?php $this->print_render_attribute_string( $attribute_ids['results_wrapper'] ); ?>>
						<div class="e-search-results"></div>
						<?php $this->render_loader( $settings ); ?>
					</output>
					<?php endif; ?>
				</div>
				<?php if ( $this->get_dropdown_width() ) : ?>
					<output <?php $this->print_render_attribute_string( $attribute_ids['results_wrapper'] ); ?>>
						<div class="e-search-results"></div>
						<?php $this->render_loader( $settings ); ?>
					</output>
				<?php endif; ?>

				<?php do_action( 'elementor_pro/search/after_input', $this ); ?>

				<button <?php $this->print_render_attribute_string( $attribute_ids['submit_button'] ); ?>>
					<?php $this->maybe_render_icon( 'icon_submit' ); ?>

					<?php if ( ! empty( $settings['submit_button_text'] ) ) : ?>
					<span <?php $this->print_render_attribute_string( $attribute_ids['submit_text'] ); ?>>
						<?php echo esc_html( $settings['submit_button_text'] ); ?>
					</span>
					<?php endif; ?>
				</button>
				<input <?php $this->print_render_attribute_string( $attribute_ids['widget_props'] ); ?>>
			</form>
		</search>
		<?php
	}

	public function query_posts() {
		$this->query = $this->get_query_args();
	}

	public function get_query_name() {
		return $this->get_name();
	}

	private function add_render_attibutes_pagination() {
		$pagination_class = 'elementor-pagination';

		$this->add_render_attribute( $this->element_attribute_ids['nav'], [
			'class' => $pagination_class,
		] );
	}

	private function add_render_attibutes() {
		$id = $this->get_id();
		$settings = $this->get_settings_for_display();
		$screen_only_class = 'elementor-screen-only';

		$this->add_render_attribute( $this->element_attribute_ids['search_wrapper'], [
			'class' => [ 'e-search', 'hidden' ],
			'role' => 'search',
		] );

		if ( 'previous_next' === $settings['pagination_type_options'] ) {
			$this->add_render_attribute( $this->element_attribute_ids['results_wrapper'], [
				'class' => 'hide-pagination-numbers',
			] );
		}

		$this->add_render_attribute( $this->element_attribute_ids['form'], [
			'class' => 'e-search-form',
			'action' => esc_url( home_url() ),
			'method' => 'get',
		] );

		$this->add_render_attribute( $this->element_attribute_ids['label'], [
			'class' => 'e-search-label',
			'for' => "search-$id",
		] );

		$this->add_render_attribute( $this->element_attribute_ids['label_text'], [
			'class' => $screen_only_class,
		] );

		$this->add_render_attribute( $this->element_attribute_ids['input_wrapper'], [
			'class' => 'e-search-input-wrapper',
		] );

		$this->add_render_attribute( $this->element_attribute_ids['input'], [
			'id' => "search-$id",
			'placeholder' => esc_attr( $settings['search_input_placeholder_text'] ),
			'class' => 'e-search-input',
			'type' => 'search',
			'name' => 's',
			'value' => '',
			'autocomplete' => $this->get_autocomplete_state(),
			'role' => 'combobox',
			'aria-autocomplete' => 'list',
			'aria-expanded' => 'false',
			'aria-controls' => "results-$id",
			'aria-haspopup' => 'listbox',
		] );

		$this->add_render_attribute( $this->element_attribute_ids['submit_button'], [
			'class' => [
				'e-search-submit',
				$this->is_submit_button_shown() ? '' : $screen_only_class,
				$settings['submit_hover_animation'] ? 'elementor-animation-' . $settings['submit_hover_animation'] : '',
			],
			'type' => 'submit',
		] );

		if ( empty( $settings['submit_button_text'] ) ) {
			$this->add_render_attribute( $this->element_attribute_ids['submit_button'], 'aria-label', esc_html__( 'Search', 'modulix' ) );
		}

		$this->add_render_attribute( $this->element_attribute_ids['submit_text'], [
			'class' => $this->is_submit_button_shown() ? '' : $screen_only_class,
		] );

		$this->add_render_attribute( $this->element_attribute_ids['results_wrapper'], [
			'id' => "results-$id",
			'class' => 'e-search-results-container hide-loader',
			'aria-live' => 'polite',
			'aria-atomic' => 'true',
			'aria-label' => esc_attr__( 'Results for search', 'modulix' ),
			'tabindex' => '0',
		] );

		$this->add_render_attribute( $this->element_attribute_ids['widget_props'], [
			'type' => 'hidden',
			'name' => 'e_search_props',
			'value' => $this->get_id() . '-' . Pro_Utils::get_current_post_id(),
		] );
	}

	private function is_submit_button_shown() {
		return 'key_enter' !== $this->get_settings_for_display( 'submit_trigger' );
	}

	private function get_autocomplete_state() {
		return 'yes' !== $this->get_settings_for_display( 'autocomplete' ) ? 'off' : 'on';
	}

	private function get_dropdown_width() {
		return 'widget_width' === $this->get_settings_for_display( 'results_is_dropdown_width' );
	}

	private function maybe_render_icon( $target ) {
		$icon_settings = $this->get_settings_for_display( $target ) ?? [
			'library' => '',
			'value' => '',
		];

		Icons_Manager::render_icon(
			$icon_settings ?? [ '', '' ],
			[ 'aria-hidden' => 'true' ],
			'i'
		);
	}

	private function get_supported_post_types() {
		$supported_post_types = [
			'any' => esc_html__( 'All', 'modulix' ),
			'product' => esc_html__( 'Products', 'modulix' ),
			'post' => esc_html__( 'Posts', 'modulix' ),
			'page' => esc_html__( 'Pages', 'modulix' ),
		];

		$cpts = $this->get_supported_cpts();

		return array_merge( $supported_post_types, $cpts );
	}

	private function get_supported_cpts() {
		$cpts = new Collection( get_post_types(
			[
				'public' => true,
				'_builtin' => false,
			],
			'objects'
		) );

		return $cpts->except( [ 'product', 'related_products', 'elementor_library' ] )
			->map_with_keys( function ( $item, $post_type ) {
				return [ $post_type => $item->label ?? null ];
			} )
			->filter()
			->all();
	}

	private function get_property_key_prefix() {
		return $this->get_query_name() . '_query';
	}
}
