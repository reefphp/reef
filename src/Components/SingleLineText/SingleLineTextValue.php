<?php

namespace Reef\Components\SingleLineText;

use Reef\Components\Component;
use Reef\Components\ComponentValue;

class SingleLineTextValue implements ComponentValue {
	
	const COMPONENT_NAME = 'reef:single_line_text';
	
	private $Component;
	private $s_value;
	
	public function __construct(Component $Component) {
		$this->Component = $Component;
		
	}
	
	/**
	 * @inherit
	 */
	public function validate(array &$a_errors = null) : bool {
		$s_value = trim($this->s_value);
		
		if($this->Component->getConfig()['required'] && empty($s_value)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($s_input) {
		$this->s_value = $s_input;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
			'value' => $this->s_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(array $a_flat) {
		$this->s_value = $a_flat['value'];
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->s_value;
	}
}
