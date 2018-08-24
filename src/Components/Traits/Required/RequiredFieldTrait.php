<?php

namespace Reef\Components\Traits\Required;

use \Reef\Components\FieldValue;
use \Reef\Submission;

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
	public function view_form_required(FieldValue $Value) : array {
		if(!isset($this->a_declaration['required'])) {
			return [];
		}
		
		$s_required = 'data-required-if="'.htmlspecialchars($this->a_declaration['required']).'"';
		
		if($this->is_required($Value->getSubmission())) {
			$s_required .= ' required';
		}
		
		return [
			'required' => $s_required,
		];
	}
	
	/**
	 * @inherit
	 */
	public function is_required(Submission $Submission) : bool {
		
		if($this instanceof \Reef\Components\Traits\Hidable\HidableFieldInterface) {
			if(!$this->is_visible($Submission)) {
				// Hidden fields are never required
				return false;
			}
		}
		
		return $Submission->evaluateCondition($this->a_declaration['required'] ?? false) ?? false;
	}
	
}
