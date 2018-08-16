<?php

namespace Reef\Components\Condition;

use Reef\Components\FieldValue;

class ConditionValue extends FieldValue {
	
	private $b_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		
		if(($this->Field->getDeclaration()['required']??false) && !$this->b_value) {
			$this->a_errors[] = $this->Field->getForm()->trans('rf_error_required_empty');
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->b_value = (bool)($this->Field->getDeclaration()['default']??false);
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return ($this->b_value === (bool)($this->Field->getDeclaration()['default']??false));
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($m_input) {
		$this->fromStructured($m_input);
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($m_input) {
		$this->b_value = \Reef\interpretBool($m_input);
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
			(int)$this->b_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
		$this->b_value = (bool)($a_flat[0]??$this->Field->getDeclaration()['default']??false);
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
	public function toOverviewColumns() : array {
		return [
			(int)$this->b_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->b_value;
	}
}
