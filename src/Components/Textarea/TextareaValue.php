<?php

namespace Reef\Components\Textarea;

use Reef\Components\FieldValue;

class TextareaValue extends FieldValue {
	
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
		
		if(isset($a_declaration['max_length']) && $a_declaration['max_length'] > 0 && strlen($s_value) > $a_declaration['max_length']) {
			$this->a_errors[] = $this->Field->trans('error_value_too_long');
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
