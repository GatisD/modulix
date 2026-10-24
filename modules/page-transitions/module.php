<?php
namespace Modulix\Modules\PageTransitions;
// Modulix: derived from Elementor Pro 4.0.4, modules/page-transitions/module.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Manager;
use Elementor\Controls_Stack;
use Elementor\Core\Experiments\Manager as Experiments_Manager;
use Elementor\Core\Kits\Documents\Tabs\Settings_Page_Transitions;
use Elementor\Group_Control_Background;
use Elementor\Icons_Manager;
use Elementor\Utils;
use Modulix\Base\Module_Base;
use Modulix\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {

	// Module name.
	const NAME = 'page-transitions';

	// Loader types.
	const TYPE_ANIMATION = 'animation';
	const TYPE_ICON = 'icon';
	const TYPE_IMAGE = 'image';

	// Pre-loader types.
	const LOADER_CIRCLE = 'circle';
	const LOADER_CIRCLE_DASHED = 'circle-dashed';
	const LOADER_BOUNCING_DOTS = 'bouncing-dots';
	const LOADER_PULSING_DOTS = 'pulsing-dots';
	const LOADER_PULSE = 'pulse';
	const LOADER_OVERLAP = 'overlap';
	const LOADER_SPINNERS = 'spinners';
	const LOADER_NESTED_SPINNERS = 'nested-spinners';
	const LOADER_OPPOSING_NESTED_SPINNERS = 'opposing-nested-spinners';
	const LOADER_OPPOSING_NESTED_RINGS = 'opposing-nested-rings';
	const LOADER_PROGRESS_BAR = 'progress-bar';
	const LOADER_TWO_WAY_PROGRESS_BAR = 'two-way-progress-bar';
	const LOADER_REPEATING_BAR = 'repeating-bar';

	/**
	 * Module constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// For cases where the user has an older Core version.
		if ( ! class_exists( 'Elementor\Core\Kits\Documents\Tabs\Settings_Page_Transitions' ) ) {
			return;
		}

		parent::__construct();

		$this->add_actions();
	}

	/**
	 * Get the module name.
	 *
	 * @return string
	 */
	public function get_name() {
		return self::NAME;
	}

	/**
	 * Register the Page Transitions controls.
	 *
	 * @param $element    Controls_Stack
	 * @param $section_id string
	 *
	 * @return void
	 */
	public function register_controls( Controls_Stack $element, $section_id ) {
		// Remove Page Transitions Banner (from Core version).
		if ( 'section_page_transitions_teaser' !== $section_id ) {
			return;
		}

		// Delete the Teaser message.
		Plugin::elementor()->controls_manager->remove_control_from_stack(
			$element->get_unique_name(),
			[
				'section_page_transitions_teaser',
				'page_transitions_teaser',
			]
		);

		// Replace the teaser message with actual controls.
		$this->register_page_transitions_controls( $element );
	}

	/**
	 * Retrieve a control ID prefixed with the tab ID.
	 *
	 * @param string $id - Control id.
	 *
	 * @return string
	 */
	private function get_control_id( $id ) {
		$tab_id = Settings_Page_Transitions::TAB_ID;
		$tab_id = str_replace( '-', '_', $tab_id );

		return $tab_id . '_' . $id;
	}

	/**
	 * Add a Page Transitions preview button.
	 *
	 * @param Controls_Stack $controls_stack - Controls Stack context to add the button to.
	 * @param string $prefix - Button ID prefix.
	 *
	 * @return void
	 */
	private function add_preview_button( $controls_stack, $prefix ) {
		$controls_stack->add_control(
			$this->get_control_id( $prefix . '_play_button' ),
			[
				'type' => Controls_Manager::BUTTON,
				'label_block' => true,
				'text' => esc_html__( 'Preview Page Transition', 'modulix' ),
				'button_type' => 'default e-page-transition-preview',
				'separator' => 'before',
				'event' => 'elementorPageTransitions:animate',
				'condition' => [
					$this->get_control_id( 'entrance_animation' ) . '!' => '',
				],
			]
		);
	}

	/**
	 * Replace the Page Transition teaser with actual controls.
	 *
	 * @param Controls_Stack $controls_stack
	 *
	 * @return void
	 */
	public function register_page_transitions_controls( $controls_stack ) {
		/**
		 * Page Transitions
		 */
		$controls_stack->start_controls_section(
			'section_page_transitions',
			[
				'label' => esc_html__( 'Page Transitions', 'modulix' ),
				'tab' => Settings_Page_Transitions::TAB_ID,
			]
		);

		$controls_stack->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'  => $this->get_control_id( 'background' ),
				'exclude' => [ 'image', 'video' ],
				'fields_options' => [
					'background' => [
						'label' => esc_html__( 'Background', 'modulix' ),
						'default' => 'classic',
						'description' => esc_html__( 'This is the page color behind your loading animation', 'modulix' ),
					],
					'color' => [
						'default' => '#FFBC7D',
					],
				],
				'selector' => '{{WRAPPER}} e-page-transition',
			]
		);

		$controls_stack->add_responsive_control(
			$this->get_control_id( 'entrance_animation' ),
			[
				'label' => esc_html__( 'Entrance Animation', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'label_block' => true,
				// The animations are the opposite of what the user sees because the user thinks in the context
				// of a page transition, while we actually animate the overlay.
				'options' => [
					'' => esc_html__( 'None', 'modulix' ),
					'fade-out' => esc_html__( 'Fade In', 'modulix' ),
					'fade-out-down' => esc_html__( 'Fade In Down', 'modulix' ),
					'fade-out-right' => esc_html__( 'Fade In Right', 'modulix' ),
					'fade-out-up' => esc_html__( 'Fade In Up', 'modulix' ),
					'fade-out-left' => esc_html__( 'Fade In Left', 'modulix' ),
					'zoom-out' => esc_html__( 'Zoom In', 'modulix' ),
					'slide-out-down' => esc_html__( 'Slide In Down', 'modulix' ),
					'slide-out-right' => esc_html__( 'Slide In Right', 'modulix' ),
					'slide-out-up' => esc_html__( 'Slide In Up', 'modulix' ),
					'slide-out-left' => esc_html__( 'Slide In Left', 'modulix' ),
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-page-transition-entrance-animation: e-page-transition-{{VALUE}}',
				],
			]
		);

		$controls_stack->add_responsive_control(
			$this->get_control_id( 'exit_animation' ),
			[
				'label' => esc_html__( 'Exit Animation', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'label_block' => true,
				// The animations are the opposite of what the user sees because the user thinks in the context
				// of a page transition, while we actually animate the overlay.
				'options' => [
					'' => esc_html__( 'None', 'modulix' ),
					'fade-in' => esc_html__( 'Fade Out', 'modulix' ),
					'fade-in-down' => esc_html__( 'Fade Out Down', 'modulix' ),
					'fade-in-right' => esc_html__( 'Fade Out Right', 'modulix' ),
					'fade-in-up' => esc_html__( 'Fade Out Up', 'modulix' ),
					'fade-in-left' => esc_html__( 'Fade Out Left', 'modulix' ),
					'zoom-in' => esc_html__( 'Zoom Out', 'modulix' ),
					'slide-in-down' => esc_html__( 'Slide Out Down', 'modulix' ),
					'slide-in-right' => esc_html__( 'Slide Out Right', 'modulix' ),
					'slide-in-up' => esc_html__( 'Slide Out Up', 'modulix' ),
					'slide-in-left' => esc_html__( 'Slide Out Left', 'modulix' ),
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-page-transition-exit-animation: e-page-transition-{{VALUE}}',
				],
				'condition' => [
					$this->get_control_id( 'entrance_animation' ) . '!' => '',
				],
			]
		);

		$controls_stack->add_control(
			$this->get_control_id( 'animation_duration' ),
			[
				'label' => esc_html__( 'Animation Duration', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 's', 'ms', 'custom' ],
				'default' => [
					'unit' => 'ms',
					'size' => 1500,
				],
				'range' => [
					's' => [
						'max' => 5,
					],
					'ms' => [
						'max' => 5000,
					],
				],
				'condition' => [
					$this->get_control_id( 'entrance_animation' ) . '!' => '',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-page-transition-animation-duration: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_preview_button( $controls_stack, 'page_transition' );

		$controls_stack->end_controls_section();

		/**
		 * Preloader
		 */
		$controls_stack->start_controls_section(
			'section_preloader',
			[
				'label' => esc_html__( 'Preloader', 'modulix' ),
				'tab' => Settings_Page_Transitions::TAB_ID,
			]
		);

		$controls_stack->add_control(
			$this->get_control_id( 'preloader_type' ),
			[
				'label' => esc_html__( 'Type', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => esc_html__( 'None', 'modulix' ),
					self::TYPE_ANIMATION => esc_html__( 'Animation', 'modulix' ),
					self::TYPE_ICON => esc_html__( 'Icon', 'modulix' ),
					self::TYPE_IMAGE => esc_html__( 'Image', 'modulix' ),
				],
			]
		);

		$controls_stack->add_control(
			$this->get_control_id( 'preloader_icon' ),
			[
				'label' => esc_html__( 'Icon', 'modulix' ),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'default' => [
					'value' => 'fas fa-spinner',
					'library' => 'fa-solid',
				],
				'condition' => [
					$this->get_control_id( 'preloader_type' ) => 'icon',
				],
			]
		);

		$controls_stack->add_control(
			$this->get_control_id( 'preloader_image' ),
			[
				'label' => esc_html__( 'Image', 'modulix' ),
				'type' => Controls_Manager::MEDIA,
				'condition' => [
					$this->get_control_id( 'preloader_type' ) => 'image',
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$controls_stack->add_control(
			$this->get_control_id( 'preloader_animation_type' ),
			[
				'label' => esc_html__( 'Animation', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => self::LOADER_CIRCLE,
				'options' => [
					self::LOADER_CIRCLE => esc_html__( 'Circle', 'modulix' ),
					self::LOADER_CIRCLE_DASHED => esc_html__( 'Circle Dashed', 'modulix' ),
					self::LOADER_BOUNCING_DOTS => esc_html__( 'Bouncing Dots', 'modulix' ),
					self::LOADER_PULSING_DOTS => esc_html__( 'Pulsing Dots', 'modulix' ),
					self::LOADER_PULSE => esc_html__( 'Pulse', 'modulix' ),
					self::LOADER_OVERLAP => esc_html__( 'Overlap', 'modulix' ),
					self::LOADER_SPINNERS => esc_html__( 'Spinners', 'modulix' ),
					self::LOADER_NESTED_SPINNERS => esc_html__( 'Nested Spinners', 'modulix' ),
					self::LOADER_OPPOSING_NESTED_SPINNERS => esc_html__( 'Opposing Nested Spinners', 'modulix' ),
					self::LOADER_OPPOSING_NESTED_RINGS => esc_html__( 'Opposing Nested Rings', 'modulix' ),
					self::LOADER_PROGRESS_BAR => esc_html__( 'Progress Bar', 'modulix' ),
					self::LOADER_TWO_WAY_PROGRESS_BAR => esc_html__( 'Two Way Progress Bar', 'modulix' ),
					self::LOADER_REPEATING_BAR => esc_html__( 'Repeating Bar', 'modulix' ),
				],
				'condition' => [
					$this->get_control_id( 'preloader_type' ) => 'animation',
				],
			]
		);

		$controls_stack->add_control(
			$this->get_control_id( 'preloader_animation' ),
			[
				'label' => esc_html__( 'Animation', 'modulix' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => esc_html__( 'None', 'modulix' ),
					'eicon-spin' => esc_html__( 'Spinning', 'modulix' ),
					'bounce' => esc_html__( 'Bounce', 'modulix' ),
					'flash' => esc_html__( 'Flash', 'modulix' ),
					'pulse' => esc_html__( 'Pulse', 'modulix' ),
					'rubberBand' => esc_html__( 'Rubber Band', 'modulix' ),
					'shake' => esc_html__( 'Shake', 'modulix' ),
					'headShake' => esc_html__( 'Head Shake', 'modulix' ),
					'swing' => esc_html__( 'Swing', 'modulix' ),
					'tada' => esc_html__( 'Tada', 'modulix' ),
					'wobble' => esc_html__( 'Wobble', 'modulix' ),
					'jello' => esc_html__( 'Jello', 'modulix' ),
				],
				'condition' => [
					$this->get_control_id( 'preloader_type' ) => [ 'icon', 'image' ],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-preloader-animation: {{VALUE}}',
				],
			]
		);

		// Include animation speed control only for specific pre-loaders which support that.
		$included_preloaders = [
			self::LOADER_CIRCLE,
			self::LOADER_CIRCLE_DASHED,
			self::LOADER_BOUNCING_DOTS,
			self::LOADER_PULSING_DOTS,
			self::LOADER_SPINNERS,
			self::LOADER_NESTED_SPINNERS,
			self::LOADER_OPPOSING_NESTED_SPINNERS,
			self::LOADER_OPPOSING_NESTED_RINGS,
			self::LOADER_PROGRESS_BAR,
			self::LOADER_TWO_WAY_PROGRESS_BAR,
			self::LOADER_REPEATING_BAR,
		];

		$controls_stack->add_control(
			$this->get_control_id( 'preloader_animation_duration' ),
			[
				'label' => esc_html__( 'Animation Duration', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 's', 'ms', 'custom' ],
				'default' => [
					'unit' => 'ms',
					'size' => 1500,
				],
				'range' => [
					's' => [
						'max' => 5,
					],
					'ms' => [
						'max' => 5000,
					],
				],
				// Show the control only for images, icons & specific custom pre-loaders.
				'conditions' => [
					'relation' => 'or',
					'terms' => [
						[
							'name' => $this->get_control_id( 'preloader_type' ),
							'operator' => 'in',
							'value' => [
								self::TYPE_IMAGE,
								self::TYPE_ICON,
							],
						],
						[
							'relation' => 'and',
							'terms' => [
								[
									'name' => $this->get_control_id( 'preloader_type' ),
									'operator' => '=',
									'value' => self::TYPE_ANIMATION,
								],
								[
									'name' => $this->get_control_id( 'preloader_animation_type' ),
									'operator' => 'in',
									'value' => $included_preloaders,
								],
							],
						],
					],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-preloader-animation-duration: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$controls_stack->add_control(
			$this->get_control_id( 'preloader_delay' ),
			[
				'label' => esc_html__( 'Preloader Delay', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 's', 'ms', 'custom' ],
				'default' => [
					'unit' => 'ms',
					'size' => 0,
				],
				'range' => [
					's' => [
						'max' => 5,
					],
					'ms' => [
						'max' => 5000,
					],
				],
				'condition' => [
					$this->get_control_id( 'preloader_type' ) . '!' => '',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-preloader-delay: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$controls_stack->add_control(
			$this->get_control_id( 'text_heading' ),
			[
				'label' => esc_html__( 'Style', 'modulix' ),
				'type' => Controls_Manager::HEADING,
				'condition' => [
					$this->get_control_id( 'preloader_type' ) . '!' => '',
				],
			]
		);

		$controls_stack->add_control(
			$this->get_control_id( 'preloader_color' ),
			[
				'label' => esc_html__( 'Color', 'modulix' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#FFF',
				'condition' => [
					$this->get_control_id( 'preloader_type' ) => [ 'icon', 'animation' ],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-preloader-color: {{VALUE}}',
				],
			]
		);

		$controls_stack->add_responsive_control(
			$this->get_control_id( 'preloader_size' ),
			[
				'label' => esc_html__( 'Size', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default' => [
					'size' => 20,
				],
				'range' => [
					'px' => [
						'max' => 300,
					],
					'em' => [
						'max' => 30,
					],
					'rem' => [
						'max' => 30,
					],
				],
				'condition' => [
					$this->get_control_id( 'preloader_type' ) => [ 'icon', 'animation' ],
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-preloader-size: {{SIZE}}{{UNIT}}',
				],
			]
		);

		// Animation to exclude in rotation.
		$excluded_animations = [
			'eicon-spin',
			'bounce',
			'pulse',
			'rubberBand',
			'shake',
			'headShake',
			'swing',
			'tada',
			'wobble',
			'jello',
		];

		$controls_stack->add_responsive_control(
			$this->get_control_id( 'preloader_rotate' ),
			[
				'label' => esc_html__( 'Rotate', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'deg', 'grad', 'rad', 'turn' ],
				'default' => [
					'unit' => 'deg',
					'size' => 0,
				],
				'condition' => [
					$this->get_control_id( 'preloader_type' ) => 'icon',
					$this->get_control_id( 'preloader_animation' ) . '!' => $excluded_animations,
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-preloader-rotate: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$controls_stack->add_responsive_control(
			$this->get_control_id( 'preloader_width' ),
			[
				'label' => esc_html__( 'Width', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'default' => [
					'unit' => '%',
				],
				'tablet_default' => [
					'unit' => '%',
				],
				'mobile_default' => [
					'unit' => '%',
				],
				'range' => [
					'%' => [
						'min' => 1,
						'max' => 100,
					],
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
					'em' => [
						'max' => 100,
					],
					'rem' => [
						'max' => 100,
					],
					'vw' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'condition' => [
					$this->get_control_id( 'preloader_type' ) => 'image',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-preloader-width: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$controls_stack->add_responsive_control(
			$this->get_control_id( 'preloader_max_width' ),
			[
				'label' => esc_html__( 'Max Width', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'default' => [
					'unit' => '%',
				],
				'tablet_default' => [
					'unit' => '%',
				],
				'mobile_default' => [
					'unit' => '%',
				],
				'range' => [
					'%' => [
						'min' => 1,
						'max' => 100,
					],
					'px' => [
						'min' => 1,
						'max' => 1000,
					],
					'em' => [
						'max' => 100,
					],
					'rem' => [
						'max' => 100,
					],
					'vw' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'condition' => [
					$this->get_control_id( 'preloader_type' ) => 'image',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-preloader-max-width: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$controls_stack->add_responsive_control(
			$this->get_control_id( 'preloader_opacity' ),
			[
				'label' => esc_html__( 'Opacity', 'modulix' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1,
						'step' => 0.1,
					],
				],
				'condition' => [
					$this->get_control_id( 'preloader_type' ) => 'image',
				],
				'selectors' => [
					'{{WRAPPER}}' => '--e-preloader-opacity: {{SIZE}}',
				],
			]
		);

		$this->add_preview_button( $controls_stack, 'preloader' );

		$controls_stack->end_controls_section();
	}

	/**
	 * Get a control value from the settings.
	 *
	 * @param string $control - Non prefixed control name.
	 *
	 * @return mixed
	 */
	private function get_setting( $control ) {
		$document = Plugin::elementor()->kits_manager->get_active_kit_for_frontend();
		$control = $this->get_control_id( $control );

		return $document->get_settings_for_display( $control );
	}

	/**
	 * Get the Page Transitions element CSS class.
	 *
	 * @return string
	 */
	private function get_page_transition_class() {
		$is_preview_mode = Plugin::elementor()->preview->is_preview_mode();
		return $is_preview_mode ? 'e-page-transition--entered' : 'e-page-transition--entering';
	}

	/**
	 * Get the Page Transitions links Regex filter.
	 *
	 * @return string
	 */
	private function get_page_transition_filter() {
		// Prepare the admin URL to be "regex-ready" (escape special characters).
		$admin_url = preg_quote( get_admin_url(), '/' );

		// A regex pattern for URLs under `wp-admin`.
		return '^' . $admin_url;
	}

	/**
	 * Print the Page Transition element attributes.
	 *
	 * @return void
	 */
	private function print_render_attribute_string() {
		$kit = Plugin::elementor()->kits_manager->get_active_kit();

		$settings = [
			'preloader_type',
			'preloader_icon',
			'preloader_image',
			'preloader_animation_type',
		];

		foreach ( $settings as $setting ) {
			$key = str_replace( '_', '-', $setting );
			$value = $this->get_setting( $setting );

			if ( empty( $value ) ) {
				continue;
			}

			// Change the key & value specifically for the image control, since the returned value
			// is an array while the Page Transition element expects a URL as a string.
			if ( 'preloader-image' === $key ) {
				$key = 'preloader-image-url';
				$value = $value['url'];
			}

			$kit->add_render_attribute( Settings_Page_Transitions::TAB_ID, $key, $value );
		}

		$class = $this->get_page_transition_class();

		if ( $class ) {
			$kit->add_render_attribute( Settings_Page_Transitions::TAB_ID, 'class', $class );
		}

		// Add URL regex filter to filter only URLs without `wp-admin`.
		$kit->add_render_attribute( Settings_Page_Transitions::TAB_ID, 'exclude', $this->get_page_transition_filter() );

		$kit->print_render_attribute_string( Settings_Page_Transitions::TAB_ID );
	}

	/**
	 * Determine if the Page Transition element should be rendered.
	 *
	 * @return bool
	 */
	private function should_render() {
		// Don't render the Page Transition if the page is a non-interactive (static-rendered) page (e.g. template-preview).
		if ( Plugin::elementor()->frontend->is_static_render_mode() ) {
			return false;
		}

		$has_entrance_animation = ! ! $this->get_setting( 'entrance_animation' );
		$has_preloader = ! ! $this->get_setting( 'preloader_type' );
		$is_page = ( is_singular() || is_archive() ) && ! is_paged();

		return $is_page && ( $has_entrance_animation || $has_preloader );
	}

	/**
	 * Whether the Page Transitions scripts should be enqueued.
	 * When in preview mode, the scripts should be loaded since the user might not have a Page Transition
	 * set on initial load but he will need them when changing the settings.
	 *
	 * @return bool
	 */
	private function should_enqueue_scripts() {
		return $this->should_render() || Plugin::elementor()->preview->is_preview_mode();
	}

	/**
	 * Render the Page Transition markup.
	 *
	 * @return void
	 */
	private function render() {
		$is_inline_font_icon_active = Plugin::elementor()->experiments->is_feature_active( 'e_font_icon_svg' );

		?>
		<e-page-transition <?php $this->print_render_attribute_string(); ?>>
			<?php
			$icon = $this->get_setting( 'preloader_icon' );

			// Render inline SVG icon when the experiment is active, since the component itself
			// shouldn't know about the Editor or the experiments.
			if ( $is_inline_font_icon_active && ! empty( $icon ) ) {
				Icons_Manager::render_icon( $icon, [ 'class' => 'e-page-transition--preloader' ] );
			}
			?>
		</e-page-transition>
		<?php
	}

	/**
	 * Load `instant-page` library for better performance.
	 *
	 * Ref: https://instant.page/
	 *
	 * @return void
	 */
	private function enqueue_instant_page_script() {
		$suffix = Utils::is_script_debug() ? '' : '.min';

		wp_enqueue_script(
			'instant-page',
			MODULIX_ASSETS_URL . "/lib/instant-page/instant-page{$suffix}.js",
			null,
			MODULIX_VERSION,
			true
		);

		// Load instant-page as module.
		add_filter( 'script_loader_tag', function ( $tag, $handle ) {
			if ( 'instant-page' === $handle ) {
				$tag = str_replace( 'text/javascript', 'module', $tag );
			}
			return $tag;
		}, 10, 2 );
	}

	/**
	 * Enqueue frontend scripts.
	 *
	 * @return void
	 */
	private function enqueue_scripts() {
		$this->enqueue_instant_page_script();

		wp_enqueue_script(
			'page-transitions',
			$this->get_js_assets_url( 'page-transitions' ),
			null,
			MODULIX_VERSION,
			false
		);
	}

	/**
	 * Get the base URL for assets.
	 *
	 * @return string
	 */
	public function get_assets_base_url() {
		return MODULIX_URL;
	}

	/**
	 * Add actions & filters.
	 *
	 * @return void
	 */
	private function add_actions() {
		add_action( 'elementor/element/after_section_end', [ $this, 'register_controls' ], 10, 2 );

		add_action( 'wp_enqueue_scripts', function () {
			if ( $this->should_enqueue_scripts() ) {
				$this->enqueue_scripts();
			}
		} );

		// Render the Page Transition element after the body open tag.
		add_action( 'wp_body_open', function () {
			if ( $this->should_render() ) {
				$this->render();
			}
		}, 10, 2 );
	}
}
