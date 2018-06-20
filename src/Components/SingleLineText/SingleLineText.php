<?php

namespace Reef\Components\SingleLineText;

use Reef\Components\Component;

class SingleLineText implements Component {
	
	const COMPONENT_NAME = 'reef:single_line_text';
	
	private $a_config;
	
	public function __construct(array $a_config) {
		$this->a_config = $a_config;
		
	}
	
	/**
	 * @inherit
	 */
	public static function getDir() : string {
		return __DIR__.'/';
	}
	
	/**
	 * @inherit
	 */
	public function newValue() : SingleLineTextValue {
		return new SingleLineTextValue($this);
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
	public function view_builder() : array {
		
		
	}
	
	/**
	 * @inherit
	 */
	public function view_form($m_value) : array {
		$a_vars = $this->a_config;
		$a_vars['value'] = (string)$m_value;
		return $a_vars;
	}
}
