<?php

namespace Reef\Components\TextNumber;

use Reef\Components\SingleLineText\SingleLineTextValue;

class TextNumberValue extends SingleLineTextValue {
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$b_valid = parent::validate();
		
		if($this->s_value != '' && !is_numeric($this->s_value)) {
			$this->a_errors[] = $this->Field->trans('error_not_a_number');
			$b_valid = false;
		}
		else {
			$f_value = (float)$this->s_value;
			$a_declaration = $this->Field->getDeclaration();
			
			$b_hasMin = isset($a_declaration['min']) && strlen($a_declaration['min']) > 0;
			$b_hasMax = isset($a_declaration['max']) && strlen($a_declaration['max']) > 0;
			
			if($b_hasMin && $f_value < $a_declaration['min']) {
				$b_valid = false;
				$this->a_errors[] = $this->Field->trans($b_hasMax ? 'error_number_min_max' : 'error_number_min');
			}
			else if($b_hasMax && $f_value > $a_declaration['max']) {
				$b_valid = false;
				$this->a_errors[] = $this->Field->trans($b_hasMin ? 'error_number_min_max' : 'error_number_max');
			}
		}
		
		return $b_valid;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		parent::fromDefault();
		if($this->s_value !== '') {
			$this->s_value = (float)$this->s_value;
		}
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($s_input) {
		parent::fromUserInput($s_input);
		if($this->s_value !== '') {
			$this->s_value = (float)$this->s_value;
		}
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
		parent::fromFlat($a_flat);
		if($this->s_value !== '') {
			$this->s_value = (float)$this->s_value;
		}
	}
	
	/**
	 * @inherit
	 */
	public function fromUpdate($OldValue) {
		$this->s_value = $OldValue->s_value;
		
		$a_declaration = $this->Field->getDeclaration();
		if(isset($a_declaration['min']) && $this->s_value < $a_declaration['min']) {
			$this->s_value = $a_declaration['min'];
		}
		if(isset($a_declaration['max']) && $this->s_value > $a_declaration['max']) {
			$this->s_value = $a_declaration['max'];
		}
		
		$this->a_errors = null;
	}
}
