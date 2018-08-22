<?php

namespace Reef\Components\Traits\Required;

use \Reef\Components\FieldValue;

trait RequiredFieldTrait {
	
	abstract public function getForm();
	
	/**
	 * @inherit
	 */
	public function validateDeclaration_required(array &$a_errors = null) : bool {
		$b_valid = true;
		
		if(isset($this->a_declaration['required']) && !$this->getForm()->getConditionEvaluator()->validate($this->a_declaration['required'], $a_errors)) {
			$b_valid = false;
		}
		
		return $b_valid;
	}
	
	/**
	 * @inherit
	 */
	public function view_form_required(?FieldValue $Value) : array {
		if(!isset($this->a_declaration['required'])) {
			return [];
		}
		
		$s_required = 'data-required-if="'.htmlspecialchars($this->a_declaration['required']).'"';
		
		if($Value->getSubmission()->evaluateCondition($this->a_declaration['required'])) {
			$s_required .= ' required';
		}
		
		return [
			'required' => $s_required,
		];
	}
	
}
