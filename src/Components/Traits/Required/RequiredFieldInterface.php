<?php

namespace Reef\Components\Traits\Required;

use \Reef\Components\FieldValue;
use \Reef\Submission;

interface RequiredFieldInterface {
	
	/**
	 * Validate the required condition of a field
	 * @param array &$a_errors (Out) Optional, array of errors
	 * @return bool True if the required condition is valid, false otherwise
	 */
	public function validateDeclaration_required(array &$a_errors = null) : bool;
	
	/**
	 * Build template variables for the required attribute
	 * @param FieldValue $Value The value object, may be null for static components
	 * @return array The template variables
	 */
	public function view_form_required(FieldValue $Value) : array;
	
	/**
	 * Determine whether this field is required within a submission
	 * @param Submission $Submission The submission
	 * @return bool True if the field is required, false otherwise
	 */
	public function is_required(Submission $Submission) : bool;
	
}
