<?php

namespace Reef\Components\Textarea;

use Reef\Components\FieldValue;
use \Reef\Components\Traits\Required\RequiredFieldValueInterface;
use \Reef\Components\Traits\Required\RequiredFieldValueTrait;

class TextareaValue extends FieldValue implements RequiredFieldValueInterface {
	
	use RequiredFieldValueTrait;
	
	protected $s_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		$s_value = trim($this->s_value);
		
		if(!$this->validate_required($s_value == '', $this->a_errors)) {
			return false;
		}
		
		if(strlen($s_value) > $this->Field->getMaxLength()) {
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
	public function fromFlat(array $a_flat) {
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
	public function validateConditionOperation(string $s_operator, $m_operand) {
		if(in_array($s_operator, ['is empty', 'is not empty'])) {
			if(!empty($m_operand)) {
				throw new \Reef\Exception\ConditionException('Empty does not take an operand');
			}
		}
		
		if(in_array($s_operator, ['is longer than', 'is shorter than'])) {
			if(!is_numeric($m_operand)) {
				throw new \Reef\Exception\ConditionException('Operand to longer/shorter should be numeric');
			}
		}
	}
	
	/**
	 * @inherit
	 */
	public function evaluateConditionOperation(string $s_operator, $m_operand) : bool {
		switch($s_operator) {
			case 'is empty':
				return trim($this->s_value) === '';
				
			case 'is not empty':
				return trim($this->s_value) !== '';
				
			case 'is longer than':
				return strlen($this->s_value) > $m_operand;
				
			case 'is shorter than':
				return strlen($this->s_value) < $m_operand;
			
			// @codeCoverageIgnoreStart
			default:
				throw new \Reef\Exception\ConditionException("Invalid operator");
			// @codeCoverageIgnoreEnd
		}
	}
}
