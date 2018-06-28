<?php

namespace Reef\Components\TextNumber;

use Reef\Components\SingleLineText\SingleLineTextField;

class TextNumberField extends SingleLineTextField {
	
	/**
	 * @inherit
	 */
	public function newValue() {
		return new TextNumberValue($this);
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
		$a_vars['value'] = $a_vars['value'];
		$a_vars['hasMin'] = isset($this->a_config['min']);
		$a_vars['hasMax'] = isset($this->a_config['max']);
		return $a_vars;
	}
	
	protected function getLanguageReplacements() : array {
		return \Reef\array_subset($this->a_config, ['min', 'max']);
	}
}
