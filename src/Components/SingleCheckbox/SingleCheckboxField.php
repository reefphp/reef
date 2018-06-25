<?php

namespace Reef\Components\SingleCheckbox;

use Reef\Components\Field;

class SingleCheckboxField extends Field {
	
	/**
	 * @inherit
	 */
	public function newValue() : SingleCheckboxValue {
		return new SingleCheckboxValue($this);
	}
	
	/**
	 * @inherit
	 */
	public function view_builder() : array {
		
		
	}
	
	/**
	 * @inherit
	 */
	public function view_form($Value, $a_options = []) : array {
		$a_vars = parent::view_form($Value, $a_options);
		$a_vars['value'] = (bool)$Value->toTemplateVar();
		return $a_vars;
	}
}
