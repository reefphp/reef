<?php

namespace Reef\Components\TextNumber;

use Reef\Components\Field;
use Reef\Components\SingleLineText\SingleLineTextField;

class TextNumberField extends SingleLineTextField {
	
	private function is_integer(string $s_var) {
		if($s_var === '') {
			return false;
		}
		
		if(substr($s_var, 0, 1) == '-') {
			$s_var = substr($s_var, 1);
		}
		
		return ctype_digit($s_var);
	}
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		$b_integer = $this->is_integer($this->a_declaration['min']) && $this->is_integer($this->a_declaration['step']);
		
		if($b_integer) {
			$b_signed = (empty($this->a_declaration['min']) && $this->a_declaration['min'] != '0') || (!empty($this->a_declaration['min']) && $this->a_declaration['min'] < 0);
			
			return [[
				'type' => \Reef\Storage\Storage::TYPE_INTEGER,
				'min' => $this->a_declaration['min']??null,
				'max' => $this->a_declaration['max']??null,
			]];
		}
		else {
			return [[
				'type' => \Reef\Storage\Storage::TYPE_FLOAT,
			]];
		}
	}
	
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
		$a_vars['hasMin'] = isset($this->a_declaration['min']) && strlen($this->a_declaration['min']) > 0;
		$a_vars['hasMax'] = isset($this->a_declaration['max']) && strlen($this->a_declaration['max']) > 0;
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function needsValueUpdate($OldField, &$b_dataLoss = null) : bool {
		if(isset($this->a_declaration['max']) && (!isset($OldField->a_declaration['max']) || $OldField->a_declaration['max'] > $this->a_declaration['max'])) {
			$b_dataLoss = true;
			return true;
		}
		
		if(isset($this->a_declaration['min']) && (!isset($OldField->a_declaration['min']) || $OldField->a_declaration['min'] < $this->a_declaration['min'])) {
			$b_dataLoss = true;
			return true;
		}
		
		$b_dataLoss = false;
		return false;
	}
	
	protected function getLanguageReplacements() : array {
		return \Reef\array_subset($this->a_declaration, ['min', 'max']);
	}
}
