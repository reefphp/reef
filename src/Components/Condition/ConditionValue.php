<?php

namespace Reef\Components\Condition;

use Reef\Components\FieldValue;

class ConditionValue extends FieldValue {
	
	private $s_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		// We cannot perform any validation at this stage, because we need a Form instance for that
		
		$this->a_errors = [];
		
		return true;
	}
	
	/**
	 * Get the default value
	 * @return string
	 */
	public function getDefault() {
		if(!isset($this->Field->getDeclaration()['default'])) {
			return 'false';
		}
		
		$m_default = $this->Field->getDeclaration()['default'];
		
		if(is_bool($m_default)) {
			return $m_default ? 'true' : 'false';
		}
		
		return $m_default;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->s_value = $this->getDefault();
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return ($this->s_value === $this->getDefault());
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($m_input) {
		$this->fromStructured($m_input);
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($m_input) {
		if(is_bool($m_input)) {
			$m_input = $m_input ? 'true' : 'false';
		}
		$this->s_value = (string)$m_input;
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
			$this->s_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(array $a_flat) {
		$this->s_value = ($a_flat[0]??$this->getDefault());
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
