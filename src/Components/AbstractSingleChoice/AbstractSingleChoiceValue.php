<?php

namespace Reef\Components\AbstractSingleChoice;

use Reef\Components\FieldValue;

abstract class AbstractSingleChoiceValue extends FieldValue {
	
	protected $s_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		$s_value = trim($this->s_value);
		
		$a_declaration = $this->Field->getDeclaration();
		
		if(($a_declaration['required']??false) && $s_value == '') {
			$this->a_errors[] = $this->Field->trans('rf_error_required_empty');
			return false;
		}
		
		if($s_value != '' && !in_array($s_value, array_column($a_declaration['options'], 'name'))) {
			$this->a_errors[] = 'Invalid value';
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->s_value = $this->Field->getDeclaration()['default']??'';
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return ($this->s_value === ($this->Field->getDeclaration()['default']??''));
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($s_input) {
		$this->fromStructured($s_input);
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($s_input) {
		$this->s_value = $s_input;
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
			$this->s_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
		$this->s_value = $a_flat[0]??$this->Field->getDeclaration()['default']??'';
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toStructured() {
		return $this->s_value;
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->s_value;
	}
}
