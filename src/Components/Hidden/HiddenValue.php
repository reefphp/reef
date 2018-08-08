<?php

namespace Reef\Components\Hidden;

use Reef\Components\FieldValue;

class HiddenValue extends FieldValue {
	
	protected $s_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->s_value = '';
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return ($this->s_value === '');
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($s_input) {
		$this->s_value = $s_input;
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($s_input) {
		$this->s_value = $s_input;
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toStructured() {
		return $this->s_value;
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
		return $this->s_value;
	}
}
