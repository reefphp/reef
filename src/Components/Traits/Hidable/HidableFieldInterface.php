<?php

namespace Reef\Components\Traits\Hidable;

use \Reef\Components\FieldValue;
use \Reef\Submission;

interface HidableFieldInterface {
	
	/**
	 * Validate the hidable/visible condition of a field
	 * @param array &$a_errors (Out) Optional, array of errors
	 * @return bool True if the hidable/visible condition is valid, false otherwise
	 */
	public function validateDeclaration_hidable(array &$a_errors = null) : bool;
	
	/**
	 * Build template variables for the hidable/visible attribute
	 * @param FieldValue $Value The value object, may be null for static components
	 * @return array The template variables
	 */
	public function view_form_hidable(FieldValue $Value) : array;
	
	/**
	 * Determine whether this field is visible within a submission
	 * @param Submission $Submission The submission
	 * @return bool True if the field is visible, false otherwise
	 */
	public function is_visible(Submission $Submission) : bool;
	
}
