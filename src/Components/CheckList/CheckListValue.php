<?php

namespace Reef\Components\CheckList;

use Reef\Components\FieldValue;

class CheckListValue extends FieldValue {
	
	protected $a_list;
	
	private function getCheckNames() {
		return array_column($this->Field->getDeclaration()['options'], 'name');
	}
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		
		$a_missingValues = array_diff($this->getCheckNames(), array_keys($this->a_list));
		
		if(count($a_missingValues) > 0) {
			$this->a_errors[] = 'Missing values for '.implode(', ', $a_missingValues).' ';
			return false;
		}
		
		return true;
	}
	
	private function getDefault() {
		$a_default = [];
		foreach($this->Field->getDeclaration()['options'] as $a_option) {
			$a_default[$a_option['name']] = $a_option['default']??false;
		}
		return $a_default;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->a_list = $this->getDefault();
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return ($this->a_list == $this->getDefault());
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($a_input) {
		$this->fromStructured($a_input);
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($a_input) {
		$this->a_list = [];
		
		foreach($this->Field->getDeclaration()['options'] as $a_option) {
			$this->a_list[$a_option['name']] = \Reef\interpretBool($a_input[$a_option['name']]??false);
		}
		
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return array_map('intval', $this->a_list);
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
		$this->a_list = array_map('boolval', $a_flat);
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toStructured() {
		return $this->a_list;
	}
	
	/**
	 * @inherit
	 */
	public function toOverviewColumns() : array {
		return array_map('intval', $this->a_list);
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->a_list;
	}
}
