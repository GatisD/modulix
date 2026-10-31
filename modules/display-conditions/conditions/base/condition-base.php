<?php
namespace Modulix\Modules\DisplayConditions\Conditions\Base;
// Modulix: derived from Elementor Pro 4.0.4, modules/display-conditions/conditions/base/condition-base.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-24): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Stack;
use Modulix\Core\Isolation\Wordpress_Adapter_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Condition_Base extends Controls_Stack {

	/**
	 * @var Wordpress_Adapter_Interface
	 */
	protected $wordpress_adapter;

	public function __construct( array $data = [] ) {
		$this->wordpress_adapter = array_pop( $data );
		parent::__construct( $data );
	}

	abstract public function get_label();

	abstract public function get_options();

	public function get_group() {
		return 'other';
	}

	public function check( $args ) : bool {
		return true;
	}

	protected function register_controls() {
		$this->start_controls_section(
			'__settings'
		);

		$this->get_options();

		$this->end_controls_section();
	}

	protected function get_initial_config() {
		$config = parent::get_initial_config();

		$config['label'] = $this->get_label();
		$config['group'] = $this->get_group();

		return $config;
	}
}
