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
	 * Parse the value from user input
	 * @param mixed $m_input The user input for the field, e.g. from $_POST
	 */
	abstract public function fromUserInput($m_input);
	
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
	 * Prepare the values to be used in the form template
	 * @return mixed
	 */
	abstract public function toTemplateVar();
	
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
