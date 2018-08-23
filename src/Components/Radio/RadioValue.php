<?php

namespace Reef\Components\Radio;

use Reef\Components\AbstractSingleChoice\AbstractSingleChoiceValue;
use \Reef\Components\Traits\Required\RequiredFieldValueInterface;
use \Reef\Components\Traits\Required\RequiredFieldValueTrait;

class RadioValue extends AbstractSingleChoiceValue implements RequiredFieldValueInterface {
	
	use RequiredFieldValueTrait;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		if(!parent::validate()) {
			return false;
		}
		
		$s_value = trim($this->s_value);
		if(!$this->validate_required($s_value == '', $this->a_errors)) {
			return false;
		}
		
		return true;
	}
	
}
