<?php
namespace Modulix\Modules\ThemeBuilder\Conditions;
// Modulix: derived from Elementor Pro 4.0.4, modules/theme-builder/conditions/condition-base.php. Copyright (C) 2016-2024 Elementor Ltd., GPL-3.0-or-later.
// Modified by Modulix (2026-10-23): namespace ElementorPro -> Modulix, text domain -> modulix.

use Elementor\Controls_Stack;
use Elementor\Core\Utils\Exceptions;
use Modulix\Modules\ThemeBuilder\Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class Condition_Base extends Controls_Stack {

	protected $sub_conditions = [];

	public static function get_priority() {
		return 100;
	}

	abstract public function get_label();

	public function get_unique_name() {
		return 'condition_' . $this->get_name();
	}

	public static function get_type() {
		throw new \Exception( 'Please overwrite the method.', Exceptions::INTERNAL_SERVER_ERROR );
	}

	public function check( $args ) {
		return false;
	}

	public function get_sub_conditions() {
		return $this->sub_conditions;
	}

	public function get_all_label() {
		return $this->get_label();
	}

	protected function get_initial_config() {
		$config = parent::get_initial_config();

		$config['label'] = $this->get_label();
		$config['sub_conditions'] = $this->get_sub_conditions();
		$config['all_label'] = $this->get_all_label();

		return $config;
	}

	public function register_sub_conditions() {}

	/**
	 * @param self $condition
	 */
	public function register_sub_condition( $condition ) {
		$conditions_manager = Module::instance()->get_conditions_manager();
		$conditions_manager->register_condition_instance( $condition );
		$this->sub_conditions[] = $condition->get_name();
	}

	public function __construct( array $data = [] ) {
		parent::__construct( $data );

		$this->register_sub_conditions();
	}
}
