<?php

namespace Reef\Components\CheckList;

use Reef\Components\FieldValue;

class CheckListValue extends FieldValue {
	
	protected $a_list;
	
	private function getCheckNames() {
		return array_column($this->Field->getDeclaration()['options'], 'name');
	}
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		
		$a_missingValues = array_diff($this->getCheckNames(), array_keys($this->a_list));
		
		if(count($a_missingValues) > 0) {
			$this->a_errors[] = 'Missing values for '.implode(', ', $a_missingValues).' ';
			return false;
		}
		
		return true;
	}
	
	private function getDefault() {
		$a_default = [];
		foreach($this->Field->getDeclaration()['options'] as $a_option) {
			$a_default[$a_option['name']] = $a_option['default']??false;
		}
		return $a_default;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->a_list = $this->getDefault();
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return ($this->a_list == $this->getDefault());
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($a_input) {
		$this->fromStructured($a_input);
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($a_input) {
		$this->a_list = [];
		
		foreach($this->Field->getDeclaration()['options'] as $a_option) {
			$this->a_list[$a_option['name']] = \Reef\interpretBool($a_input[$a_option['name']]??false);
		}
		
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return array_map('intval', $this->a_list);
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(array $a_flat) {
		$this->a_list = array_map('boolval', $a_flat);
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toStructured() {
		return $this->a_list;
	}
	
	/**
	 * @inherit
	 */
	public function toOverviewColumns() : array {
		return array_map('intval', $this->a_list);
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->a_list;
	}
	
	/**
	 * @inherit
	 */
	public function validateConditionOperation(string $s_operator, $m_operand) {
		
		if(in_array($s_operator, ['has checked', 'has not checked'])) {
			$b_found = false;
			foreach($this->getField()->getDeclaration()['options'] as $a_option) {
				if($a_option['name'] === $m_operand) {
					$b_found = true;
					break;
				}
			}
			if(!$b_found) {
				throw new \Reef\Exception\ConditionException('Invalid operand "'.$m_operand.'"');
			}
		}
		
		if(in_array($s_operator, ['at least checked', 'at most checked', 'at least unchecked', 'at most unchecked'])) {
			if(!is_numeric($m_operand)) {
				throw new \Reef\Exception\ConditionException('Check counting requires a numeric operand');
			}
		}
	}
	
	/**
	 * @inherit
	 */
	public function evaluateConditionOperation(string $s_operator, $m_operand) : bool {
		switch($s_operator) {
			case 'has checked':
				return $this->a_list[$m_operand];
				
			case 'has not checked':
				return !$this->a_list[$m_operand];
				
			case 'at least checked':
				return count(array_filter($this->a_list)) >= $m_operand;
				
			case 'at most checked':
				return count(array_filter($this->a_list)) <= $m_operand;
				
			case 'at least unchecked':
				return count(array_filter($this->a_list, function($b) { return !$b; })) >= $m_operand;
				
			case 'at most unchecked':
				return count(array_filter($this->a_list, function($b) { return !$b; })) <= $m_operand;
			
			// @codeCoverageIgnoreStart
			default:
				throw new \Reef\Exception\ConditionException("Invalid operator");
			// @codeCoverageIgnoreEnd
		}
	}
}
