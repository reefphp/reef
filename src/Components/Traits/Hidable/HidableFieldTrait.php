<?php

namespace Reef\Components\Traits\Hidable;

use \Reef\Components\FieldValue;
use \Reef\Submission\Submission;
use \Reef\Form\Form;

trait HidableFieldTrait {
	
	abstract public function getForm() : Form;
	
	/**
	 * @inherit
	 */
	public function validateDeclaration_hidable(array &$a_errors = null) : bool {
		$b_valid = true;
		
		if(isset($this->a_declaration['visible']) && !$this->getForm()->getConditionEvaluator()->validate($this->a_declaration['visible'], $a_errors)) {
			$b_valid = false;
		}
		
		return $b_valid;
	}
	
	/**
	 * @inherit
	 */
	public function view_form_hidable(FieldValue $Value) : array {
		if(!isset($this->a_declaration['visible'])) {
			return [];
		}
		
		$s_attributes = 'data-visible-if="'.\Reef\escapeHTML($this->a_declaration['visible']).'"';
		
		if(!$this->is_visible($Value->getSubmission())) {
			$s_attributes .= ' data-'.$this->getForm()->getReef()->getOption('css_prefix').'hidable-hidden="1"';
		}
		
		return [
			'field_attributes' => $s_attributes,
		];
	}
	
	/**
	 * @inherit
	 */
	public function is_visible(Submission $Submission) : bool {
		return $Submission->evaluateCondition($this->a_declaration['visible'] ?? true) ?? true;
	}
	
}
