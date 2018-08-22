<?php

namespace Reef\Components\TextLine;

use Reef\Components\FieldValue;
use \Reef\Components\Traits\Required\RequiredFieldValueInterface;
use \Reef\Components\Traits\Required\RequiredFieldValueTrait;

class TextLineValue extends FieldValue implements RequiredFieldValueInterface {
	
	use RequiredFieldValueTrait;
	
	protected $s_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		$s_value = trim($this->s_value);
		
		$a_declaration = $this->Field->getDeclaration();
		
		if(!$this->validate_required($s_value == '', $this->a_errors)) {
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
	
	/**
	 * @inherit
	 */
	public function evaluateCondition(string $s_operator, $m_operand) : bool {
		switch($s_operator) {
			case 'equals':
				return $this->s_value == $m_operand;
				
			case 'does not equal':
				return $this->s_value != $m_operand;
				
			case 'contains':
				if(empty($m_operand)) {
					throw new \Reef\Exception\ValidationException([-1 => 'Empty operand']);
				}
				
				return strpos($this->s_value, $m_operand) !== false;
				
			case 'does not contain':
				if(empty($m_operand)) {
					throw new \Reef\Exception\ValidationException([-1 => 'Empty operand']);
				}
				
				return strpos($this->s_value, $m_operand) === false;
		}
	}
}
