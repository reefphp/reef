<?php

namespace Reef\Components\SingleCheckbox;

use Reef\Components\FieldValue;

class SingleCheckboxValue extends FieldValue {
	
	private $b_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		
		if(($this->Field->getConfig()['required']??false) && !$this->b_value) {
			$this->a_errors[] = $this->Field->getForm()->trans('rf_error_required_empty');
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->b_value = (bool)($this->Field->getConfig()['default']??false);
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($m_input) {
		if(is_string($m_input)) {
			$m_input = strtolower($m_input);
			$m_input = ($m_input != 'false' && $m_input != 'no' && $m_input != '0');
		}
		
		$this->b_value = (bool)$m_input;
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
			$this->b_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
		$this->b_value = (bool)($a_flat[0]??$this->Field->getConfig()['default']??false);
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toStructured() {
		return $this->b_value;
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->b_value;
	}
}
