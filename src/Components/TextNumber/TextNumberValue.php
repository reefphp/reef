<?php

namespace Reef\Components\TextNumber;

use Reef\Components\FieldValue;
use \Reef\Components\Traits\Required\RequiredFieldValueInterface;
use \Reef\Components\Traits\Required\RequiredFieldValueTrait;

class TextNumberValue extends FieldValue implements RequiredFieldValueInterface {
	
	use RequiredFieldValueTrait;
	
	protected $m_rawValue;
	protected $f_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$b_valid = true;
		$this->a_errors = [];
		$a_declaration = $this->Field->getDeclaration();
		
		if(!$this->validate_required(trim($this->m_rawValue) == '', $this->a_errors)) {
			$b_valid = false;
		}
		
		if($this->m_rawValue != '' && !is_numeric($this->m_rawValue)) {
			$this->a_errors[] = $this->Field->trans('error_not_a_number');
			$b_valid = false;
		}
		else if($this->m_rawValue != '') {
			$f_value = (float)$this->f_value;
			
			// Check min/max
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
			
			// Check step
			$i_step = !empty($a_declaration['step']) ? $a_declaration['step'] : 1;
			$f_base = $b_hasMin ? $a_declaration['min'] : 0;
			$f_mul = ($f_value - $f_base) / $i_step;
			
			// f_mul should be integer
			if(abs($f_mul - round($f_mul)) > 0.001) {
				$b_valid = false;
				$this->a_errors[] = "Please fill in a multiple of ".$i_step;
			}
		}
		
		return $b_valid;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->f_value = $this->m_rawValue = $this->Field->getDeclaration()['default']??'';
		$this->a_errors = null;
		if($this->f_value !== '') {
			$this->f_value = $this->Field->is_integer() ? (int)$this->f_value : (float)$this->f_value;
		}
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return ((string)$this->f_value === (string)($this->Field->getDeclaration()['default']??''));
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($s_input) {
		$this->fromStructured($s_input);
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($s_input) {
		$this->f_value = $this->m_rawValue = $s_input;
		$this->a_errors = null;
		if($this->f_value !== '') {
			$this->f_value = $this->Field->is_integer() ? (int)$this->f_value : (float)$this->f_value;
		}
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
			$this->f_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
		$this->f_value = $this->m_rawValue = $a_flat[0]??$this->Field->getDeclaration()['default']??'';
		$this->a_errors = null;
		if($this->f_value !== '') {
			$this->f_value = $this->Field->is_integer() ? (int)$this->f_value : (float)$this->f_value;
		}
	}
	
	/**
	 * @inherit
	 */
	public function toStructured() {
		return $this->f_value;
	}
	
	/**
	 * @inherit
	 */
	public function toOverviewColumns() : array {
		return [
			$this->f_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->f_value;
	}
}
