<?php

namespace Reef\Components\Checkbox;

use Reef\Components\FieldValue;
use \Reef\Components\Traits\Required\RequiredFieldValueInterface;
use \Reef\Components\Traits\Required\RequiredFieldValueTrait;

class CheckboxValue extends FieldValue implements RequiredFieldValueInterface {
	
	use RequiredFieldValueTrait;
	
	private $b_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		
		if(!$this->validate_required(!$this->b_value, $this->a_errors)) {
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
	public function fromFlat(array $a_flat) {
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
	
	/**
	 * @inherit
	 */
	public function validateConditionOperation(string $s_operator, $m_operand) {
		if(!empty($m_operand)) {
			throw new \Reef\Exception\ConditionException('Checked does not take an operand');
		}
	}
	
	/**
	 * @inherit
	 */
	public function evaluateConditionOperation(string $s_operator, $m_operand) : bool {
		switch($s_operator) {
			case 'is checked':
				return $this->b_value;
				
			case 'is not checked':
				return !$this->b_value;
			
			// @codeCoverageIgnoreStart
			default:
				throw new \Reef\Exception\ConditionException("Invalid operator");
			// @codeCoverageIgnoreEnd
		}
	}
}
