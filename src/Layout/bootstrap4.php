<?php

namespace Reef\Layout;

class bootstrap4 implements Layout {
	
	private $a_config;
	
	public function __construct($a_config = []) {
		$this->a_config = array_merge([
			'col_left' => 'col-12 col-md-3',
			'col_right' => 'col-12 col-md-9',
		], $a_config);
	}
	
	/**
	 * @inherit
	 */
	public static function getName() : string {
		return 'bootstrap4';
	}
	
	/**
	 * @inherit
	 */
	public function getConfig() : array {
		return $this->a_config;
	}
	
	/**
	 * @inherit
	 */
	public function getMergedConfig(array $a_config) : array {
		return array_merge($this->getConfig(), $a_config);
	}
	
}
