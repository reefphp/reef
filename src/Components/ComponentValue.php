<?php

namespace Reef\Components;

interface ComponentValue {
	
	public function __construct(Component $Component);
	
	/**
	 * Parse the value from user input
	 * @param mixed $m_input The user input for the component, e.g. from $_POST
	 */
	public function fromUserInput($m_input);
	
	/**
	 * Serialize the current value into a flat array
	 * @return array The value
	 */
	public function toFlat() : array;
	
	/**
	 * Parse the value from a flat array created with toFlat()
	 * @param array $a_flat The flat value array
	 */
	public function fromFlat(array $a_flat);
	
	/**
	 * Prepare the values to be used in the form template
	 * @return mixed
	 */
	public function toTemplateVar();
	
	/**
	 * Determine whether the current value is valid
	 * @param &array $a_errors (Out) An array of errors
	 * @return boolean True if the current value is valid, false otherwise
	 */
	public function validate(array &$a_errors = null) : bool;
}
