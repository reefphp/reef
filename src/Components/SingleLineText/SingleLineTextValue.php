<?php

namespace Reef\Components\SingleLineText;

use Reef\Components\Component;
use Reef\Components\ComponentValue;

class SingleLineTextValue extends ComponentValue {
	
	const COMPONENT_NAME = 'reef:single_line_text';
	
	private $s_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		$s_value = trim($this->s_value);
		
		if($this->Component->getConfig()['required'] && empty($s_value)) {
			$this->a_errors[] = $this->Component->getForm()->trans('error_required_empty');
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($s_input) {
		$this->s_value = $s_input;
		$this->a_errors = null;
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
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->s_value;
	}
}
