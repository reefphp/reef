<?php

namespace Reef\Components\SingleLineText;

use Reef\Components\Field;

class SingleLineTextField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [[
			'type' => Field::TYPE_TEXT,
			'limit' => $this->a_config['max_length'] ?? 1000,
		]];
	}
	
	/**
	 * @inherit
	 */
	public function newValue() {
		return new SingleLineTextValue($this);
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
		$a_vars['value'] = (string)$Value->toTemplateVar();
		return $a_vars;
	}
}
