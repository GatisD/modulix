<?php
namespace Modulix\Review;

class Child extends \Elementor\Review\Base {
	public function exercise(): void {
		$this->real_method();
		$this->definitely_missing_method();
	}
}
