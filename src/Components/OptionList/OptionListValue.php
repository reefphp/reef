<?php

namespace Reef\Components\OptionList;

use Reef\Components\FieldValue;

class OptionListValue extends FieldValue {
	
	protected $a_value;
	
	public function askNames() {
		return \Reef\interpretBool($this->Field->getDeclaration()['names'] ?? true);
	}
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		
		$a_declaration = $this->Field->getDeclaration();
		
		if(isset($a_declaration['min_num_options']) && $a_declaration['min_num_options'] > 0 && count($this->a_value) < $a_declaration['min_num_options']) {
			$this->a_errors[] = $this->Field->trans('error_min_options');
			return false;
		}
		
		if(isset($a_declaration['max_num_options']) && $a_declaration['max_num_options'] > 0 && count($this->a_value) > $a_declaration['max_num_options']) {
			$this->a_errors[] = $this->Field->trans('error_max_options');
			return false;
		}
		
		$a_uniqueCheck = [];
		$i_defaults = 0;
		
		foreach($this->a_value as $i => $a_option) {
			if(empty($a_option['name'])) {
				$this->a_errors[] = 'Name is required';
				return false;
			}
			
			if(empty($a_option['locale'])) {
				$this->a_errors[] = 'Locale is required';
				return false;
			}
			
			if(!preg_match('/'.str_replace('/', '\\/', \Reef\Reef::NAME_REGEXP).'/', $a_option['name'])) {
				$this->a_errors[] = $this->Field->trans('error_regexp');
				return false;
			}
			
			if(isset($a_uniqueCheck[$a_option['name']])) {
				$this->a_errors[] = $this->Field->trans('error_duplicate');
				return false;
			}
			$a_uniqueCheck[$a_option['name']] = true;
			
			if(\Reef\interpretBool($a_option['default']??false)) {
				$i_defaults++;
			}
		}
		
		if(isset($a_declaration['max_checked_defaults']) && $a_declaration['max_checked_defaults'] > 0 && $i_defaults > $a_declaration['max_checked_defaults']) {
			$this->a_errors[] = $this->Field->trans('error_max_checked_defaults');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Obtain the default value
	 * @return array
	 */
	public function getDefault() {
		return (array)($this->Field->getDeclaration()['default'] ?? []);
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->a_value = $this->getDefault();
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return ($this->a_value === $this->getDefault());
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($a_input) {
		$this->fromStructured($a_input);
		$b_askNames = $this->askNames();
		
		$j = 1;
		foreach($this->a_value as $i => $a_option) {
			$this->a_value[$i] = [
				'name' => $b_askNames ? $a_option['name'] : ('option_'.$j++),
				'default' => \Reef\interpretBool($a_option['default']??false),
				'locale' => $a_option['locale'],
			];
			
			if(isset($a_option['old_name'])) {
				$this->a_value[$i]['old_name'] = $a_option['old_name'];
			}
		}
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($a_input) {
		$this->a_value = $a_input;
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
			json_encode($this->a_value),
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(array $a_flat) {
		$this->a_value = json_decode($a_flat[0]??'[]', true);
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toStructured() {
		return $this->a_value;
	}
	
	/**
	 * @inherit
	 */
	public function toOverviewColumns() : array {
		return [];
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->a_value;
	}
}
