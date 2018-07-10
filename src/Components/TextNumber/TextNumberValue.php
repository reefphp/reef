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
			$a_config = $this->Field->getConfig();
			
			$b_hasMin = isset($a_config['min']) && strlen($a_config['min']) > 0;
			$b_hasMax = isset($a_config['max']) && strlen($a_config['max']) > 0;
			
			if($b_hasMin && $f_value < $a_config['min']) {
				$b_valid = false;
				$this->a_errors[] = $this->Field->trans($b_hasMax ? 'error_number_min_max' : 'error_number_min');
			}
			else if($b_hasMax && $f_value > $a_config['max']) {
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
		
		$a_config = $this->Field->getConfig();
		if(isset($a_config['min']) && $this->s_value < $a_config['min']) {
			$this->s_value = $a_config['min'];
		}
		if(isset($a_config['max']) && $this->s_value > $a_config['max']) {
			$this->s_value = $a_config['max'];
		}
		
		$this->a_errors = null;
	}
}
