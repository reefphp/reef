<?php

namespace Reef\Components\Select;

use Reef\Components\AbstractSingleChoice\AbstractSingleChoiceField;

class SelectField extends AbstractSingleChoiceField {
	
	/**
	 * @inherit
	 */
	public function newValue() {
		return new SelectValue($this);
	}
	
	/**
	 * @inherit
	 */
	public function isRequired() {
		return false;
	}
	
}