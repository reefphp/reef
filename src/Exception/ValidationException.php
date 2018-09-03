<?php

namespace Reef\Exception;

class ValidationException extends RuntimeException {
	/**
	 * The validation errors (potentially multi-dimensional)
	 * @type array
	 */
	private $a_errors;
	
	/**
	 * The validation errors (one-dimensional array)
	 * @type string[]
	 */
	private $a_flatErrors;
	
	/**
	 * Constructor
	 * @param array $a_errors The validation errors
	 * @inherit
	 */
	public function __construct(array $a_errors, int $i_code = 0, \Throwable $previous = null) {
		
		$this->a_errors = $a_errors;
		$this->a_flatErrors = [];
		
		$this->toFlatErrors($a_errors);
		$s_message = implode('; ', $this->a_flatErrors);
		
		parent::__construct($s_message, $i_code, $previous);
	}
	
	/**
	 * Get the validation errors
	 * @return array The errors
	 */
	public function getErrors() {
		return $this->a_errors;
	}
	
	/**
	 * Turn the error error into a flat error array
	 * @param array $a_errors (A subset of) the validation errors
	 * @param string $s_prefix The prefix, used in recursive calls
	 */
	private function toFlatErrors(array $a_errors, $s_prefix = '') {
		
		foreach($a_errors as $s_name => $m_subErrors) {
			$s_newPrefix = is_string($s_name) ? '('.$s_name.') '.$s_prefix : $s_prefix;
			if(is_array($m_subErrors)) {
				$this->toFlatErrors($m_subErrors, $s_newPrefix);
			}
			else {
				$this->a_flatErrors[] = $s_newPrefix . $m_subErrors;
			}
		}
		
	}
}
