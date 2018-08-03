<?php

namespace Reef\Components\Radio;

use Reef\Components\AbstractSingleChoice\AbstractSingleChoiceField;

class RadioField extends AbstractSingleChoiceField {
	
	/**
	 * @inherit
	 */
	public function isRequired() {
		return (bool)($this->a_declaration['required']??false);
	}
	
}
