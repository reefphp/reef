<?php

namespace Reef\Components\Traits\Required;

use \Reef\Components\Field;
use \Reef\Submission\Submission;

trait RequiredFieldValueTrait {
	
	abstract public function getField() : Field;
	abstract public function getSubmission() : Submission;
	
	/**
	 * @inherit
	 */
	public function validate_required(bool $b_empty, array &$a_errors = null) : bool {
		if(!$b_empty) {
			return true;
		}
		
		$a_declaration = $this->getField()->getDeclaration();
		
		if(!isset($a_declaration['required'])) {
			return true;
		}
		
		if($this->getField()->is_required($this->getSubmission())) {
			$a_errors[] = $this->getField()->trans('rf_error_required_empty');
			return false;
		}
		
		return true;
	}
	
}
