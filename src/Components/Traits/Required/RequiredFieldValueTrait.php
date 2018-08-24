<?php

namespace Reef\Components\Traits\Required;

trait RequiredFieldValueTrait {
	
	abstract public function getField();
	abstract public function getSubmission();
	
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
