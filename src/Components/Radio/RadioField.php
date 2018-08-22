<?php

namespace Reef\Components\Radio;

use Reef\Components\AbstractSingleChoice\AbstractSingleChoiceField;

class RadioField extends AbstractSingleChoiceField {
	
	/**
	 * @inherit
	 */
	public function view_submission($Value, $a_options = []) : array {
		return $this->view_form($Value, $a_options);
	}
	
}
