<?php

namespace Reef\Components\Traits\Required;

interface RequiredFieldValueInterface {
	
	/**
	 * Check the field value against the required condition
	 * @param bool $b_empty True if the value is empty, false otherwise
	 * @param array &$a_errors (Out) Optional, array of errors
	 * @return bool False if the value is empty and the field is required, true otherwise
	 */
	public function validate_required(bool $b_empty, array &$a_errors = null) : bool;
	
}
