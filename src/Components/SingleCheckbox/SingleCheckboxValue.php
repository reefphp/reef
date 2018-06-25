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
		
		if($this->Field->getConfig()['required'] && !$this->b_value) {
			$this->a_errors[] = $this->Field->getForm()->trans('error_required_empty');
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
		$this->b_value = (bool)$m_input;
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
			'value' => $this->b_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
		$this->b_value = (bool)($a_flat['value']??$this->Field->getConfig()['default']??false);
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->b_value;
	}
}
