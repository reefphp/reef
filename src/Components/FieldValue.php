<?php

namespace Reef\Components;

abstract class FieldValue {
	
	protected $Field;
	protected $a_errors;
	
	public function __construct(Field $Field) {
		$this->Field = $Field;
	}
	
	/**
	 * Set the value equal to the default value
	 */
	abstract public function fromDefault();
	
	/**
	 * Determine whether the current value is equal to the default value
	 * @return bool
	 */
	abstract public function isDefault() : bool;
	
	/**
	 * Parse the value from user input
	 * @param mixed $m_input The user input for the field, e.g. from $_POST
	 */
	abstract public function fromUserInput($m_input);
	
	/**
	 * Parse the value from a structured value
	 * @param mixed $m_input The structured value for the field
	 */
	abstract public function fromStructured($m_input);
	
	/**
	 * Serialize the current value into a flat array
	 * @return array The value
	 */
	abstract public function toFlat() : array;
	
	/**
	 * Parse the value from a flat array created with toFlat()
	 * @param array $a_flat The flat value array
	 */
	abstract public function fromFlat(?array $a_flat);
	
	/**
	 * Serialize the current value into a structured value
	 * @return mixed The value
	 */
	abstract public function toStructured();
	
	/**
	 * Prepare the values to be used in the form template
	 * @return mixed
	 */
	abstract public function toTemplateVar();
	
	/**
	 * Serialize the current value into a flat array for display purposes in an overview
	 * @return array The value
	 */
	abstract public function toOverviewColumns() : array;
	
	/**
	 * Determine whether the current value is valid
	 * @return boolean True if the current value is valid, false otherwise
	 */
	abstract public function validate() : bool;
	
	/**
	 * Retrieve errors from validation
	 * @return array The errors
	 */
	public function getErrors() : ?array {
		return $this->a_errors;
	}
}
