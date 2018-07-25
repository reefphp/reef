<?php

namespace Reef\Components\TextNumber;

use Reef\Components\FieldValue;

class TextNumberValue extends FieldValue {
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$b_valid = true;
		$this->a_errors = [];
		$a_declaration = $this->Field->getDeclaration();
		
		if(($a_declaration['required']??false) && trim($this->s_value) == '') {
			$this->a_errors[] = $this->Field->trans('rf_error_required_empty');
			$b_valid = false;
		}
		
		if($this->s_value != '' && !is_numeric($this->s_value)) {
			$this->a_errors[] = $this->Field->trans('error_not_a_number');
			$b_valid = false;
		}
		else if($this->s_value != '') {
			$f_value = (float)$this->s_value;
			
			$b_hasMin = isset($a_declaration['min']) && strlen($a_declaration['min']) > 0;
			$b_hasMax = isset($a_declaration['max']) && strlen($a_declaration['max']) > 0;
			
			if($b_hasMin && $f_value < $a_declaration['min']) {
				$b_valid = false;
				$this->a_errors[] = $this->Field->trans($b_hasMax ? 'error_number_min_max' : 'error_number_min');
			}
			else if($b_hasMax && $f_value > $a_declaration['max']) {
				$b_valid = false;
				$this->a_errors[] = $this->Field->trans($b_hasMin ? 'error_number_min_max' : 'error_number_max');
			}
		}
		
		return $b_valid;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->s_value = $this->Field->getDeclaration()['default']??'';
		$this->a_errors = null;
		if($this->s_value !== '') {
			$this->s_value = $this->Field->is_integer() ? (int)$this->s_value : (float)$this->s_value;
		}
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return ((string)$this->s_value === (string)($this->Field->getDeclaration()['default']??''));
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
		if($this->s_value !== '') {
			$this->s_value = $this->Field->is_integer() ? (int)$this->s_value : (float)$this->s_value;
		}
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
		if($this->s_value !== '') {
			$this->s_value = $this->Field->is_integer() ? (int)$this->s_value : (float)$this->s_value;
		}
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
