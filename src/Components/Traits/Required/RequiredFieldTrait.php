<?php

namespace Reef\Components\Traits\Required;

use \Reef\Form\Form;
use \Reef\Components\FieldValue;
use \Reef\Submission\Submission;

trait RequiredFieldTrait {
	
	abstract public function getForm() : Form;
	
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
		$s_requiredFieldClass = '';
		
		if($this->is_required($Value->getSubmission())) {
			$s_required .= ' required';
			$s_requiredFieldClass = ' ' . $this->getForm()->getReef()->getOption('css_prefix') . 'is-required ';
		}
		
		return [
			'required' => $s_required,
			'field_classes' => $s_requiredFieldClass,
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
