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
		$b_integer = $this->is_integer($this->a_config['min']) && $this->is_integer($this->a_config['step']);
		
		if($b_integer) {
			$b_signed = (empty($this->a_config['min']) && $this->a_config['min'] != '0') || (!empty($this->a_config['min']) && $this->a_config['min'] < 0);
			
			return [[
				'type' => Field::TYPE_INTEGER,
				'signed' => $b_signed,
				'limit' => max(abs($this->a_config['min']), abs($this->a_config['max'])),
			]];
		}
		else {
			return [[
				'type' => Field::TYPE_FLOAT,
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
		$a_vars['hasMin'] = isset($this->a_config['min']) && strlen($this->a_config['min']) > 0;
		$a_vars['hasMax'] = isset($this->a_config['max']) && strlen($this->a_config['max']) > 0;
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function needsValueUpdate($OldField, &$b_dataLoss = null) : bool {
		if(isset($this->a_config['max']) && (!isset($OldField->a_config['max']) || $OldField->a_config['max'] > $this->a_config['max'])) {
			$b_dataLoss = true;
			return true;
		}
		
		if(isset($this->a_config['min']) && (!isset($OldField->a_config['min']) || $OldField->a_config['min'] < $this->a_config['min'])) {
			$b_dataLoss = true;
			return true;
		}
		
		$b_dataLoss = false;
		return false;
	}
	
	protected function getLanguageReplacements() : array {
		return \Reef\array_subset($this->a_config, ['min', 'max']);
	}
}
