<?php

namespace Reef\Components\SingleCheckbox;

use Reef\Components\Field;

class SingleCheckboxField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [[
			'type' => \Reef\Storage\Storage::TYPE_BOOLEAN,
		]];
	}
	
	/**
	 * @inherit
	 */
	public function newValue() : SingleCheckboxValue {
		return new SingleCheckboxValue($this);
	}
	
	/**
	 * @inherit
	 */
	public function isRequired() {
		return (bool)($this->a_declaration['required']??false);
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
