<?php

namespace Reef\Components\Select;

use Reef\Components\AbstractSingleChoice\AbstractSingleChoiceValue;

class SelectValue extends AbstractSingleChoiceValue {
	
	
	/**
	 * @inherit
	 */
	public function validateConditionOperation(string $s_operator, $m_operand) {
		if(in_array($s_operator, ['is empty', 'is not empty'])) {
			if(!empty($m_operand)) {
				throw new \Reef\Exception\ConditionException('Empty does not take an operand');
			}
		}
		
		if(in_array($s_operator, ['equals', 'does not equal'])) {
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
	}
	
	/**
	 * @inherit
	 */
	public function evaluateConditionOperation(string $s_operator, $m_operand) : bool {
		switch($s_operator) {
			case 'equals':
				return $this->s_value == $m_operand;
				
			case 'does not equal':
				return $this->s_value != $m_operand;
				
			case 'is empty':
				return $this->s_value == '';
				
			case 'is not empty':
				return $this->s_value != '';
		}
	}
}
