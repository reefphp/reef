<?php

namespace Reef\Components\TextLine;

use Reef\Components\FieldValue;

class TextLineValue extends FieldValue {
	
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
		
		if(strlen($s_value) > $this->Field->getMaxLength()) {
			$this->a_errors[] = $this->Field->trans('error_value_too_long');
			return false;
		}
		
		if(isset($a_declaration['regexp']) && $s_value != '') {
			if(!preg_match('/'.str_replace('/', '\\/', $a_declaration['regexp']).'/', $s_value)) {
				$this->a_errors[] = "Value does not match the regular expression";
				return false;
			}
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
	public function toOverviewColumns() : array {
		return [
			$this->s_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->s_value;
	}
}
