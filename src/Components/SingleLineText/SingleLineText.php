<?php

namespace Reef\Components\SingleLineText;

use Reef\Components\Component;

class SingleLineText implements Component {
	
	const COMPONENT_NAME = 'reef:single_line_text';
	
	private $a_config;
	
	public function __construct(array $a_config) {
		$this->a_config = $a_config;
		
	}
	
	public static function getDir() {
		return __DIR__.'/';
	}
	
	public function getConfig() : array {
		return $this->a_config;
		
	}
	
	public function view_builder() : array {
		
		
	}
	
	public function view_form($m_value) : array {
		$a_vars = $this->a_config;
		$a_vars['value'] = (string)$m_value;
		return $a_vars;
	}
	
	public function validate($m_input, array &$a_errors = null) : bool {
		$m_input = trim($m_input);
		
		if($this->a_config['required'] && empty($m_input)) {
			return false;
		}
		
		return true;
	}
	
	public function store($m_input) {
		return trim($m_input);
	}
}
