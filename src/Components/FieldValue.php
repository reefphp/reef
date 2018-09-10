<?php

namespace Reef\Components;
use \Reef\Submission\Submission;
use \Reef\Components\Traits\Hidable\HidableFieldValueInterface;
use \Reef\Components\Traits\Hidable\HidableFieldValueTrait;

/**
 * A field value represents a value submitted to a field
 */
abstract class FieldValue implements HidableFieldValueInterface {
	
	use HidableFieldValueTrait;
	
	/**
	 * The submission this value belongs to
	 * @type Submission
	 */
	protected $Submission;
	
	/**
	 * The field this value belongs to
	 * @type Field
	 */
	protected $Field;
	
	/**
	 * Array of validation errors
	 * @type ?string[]
	 */
	protected $a_errors;
	
	/**
	 * Constructor
	 * @param Submission $Submission The submission this value belongs to
	 * @param Field $Field The field this value belongs to
	 */
	public function __construct(\Reef\Submission\Submission $Submission, Field $Field) {
		$this->Submission = $Submission;
		$this->Field = $Field;
	}
	
	/**
	 * Get the field this value belongs to
	 * @return Field
	 */
	public function getField() : Field {
		return $this->Field;
	}
	
	/**
	 * Get the submission this value belongs to
	 * @return \Reef\Submission\Submission
	 */
	public function getSubmission() : Submission {
		return $this->Submission;
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
	 * @return scalar[] The value
	 */
	abstract public function toFlat() : array;
	
	/**
	 * Parse the value from a flat array created with toFlat()
	 * @param scalar[] $a_flat The flat value array
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
	 * @return scalar[] The value
	 */
	abstract public function toOverviewColumns() : array;
	
	/**
	 * Determine whether the current value is valid
	 * @return boolean True if the current value is valid, false otherwise
	 */
	abstract public function validate() : bool;
	
	/**
	 * Validate a condition operation on this field value
	 * @param string $s_operator The operator, one of Field::getConditionOperators()
	 * @param mixed $m_operand The operand
	 * 
	 * @throws BadMethodCallException If condition operations are not supported
	 * @throws ConditionException If the operation is invalid
	 */
	public function validateConditionOperation(string $s_operator, $m_operand) {
		throw new \Reef\Exception\BadMethodCallException('This component does not implement conditions');
	}
	
	/**
	 * Evaluate a condition operation on this field value
	 * @param string $s_operator The operator, one of Field::getConditionOperators()
	 * @param mixed $m_operand The operand
	 * @return boolean Result of the condition
	 */
	public function evaluateConditionOperation(string $s_operator, $m_operand) : bool {
		throw new \Reef\Exception\BadMethodCallException('This component does not implement conditions');
	}
	
	/**
	 * Retrieve errors from validation
	 * @return ?string[] The errors
	 */
	public function getErrors() : ?array {
		return $this->a_errors;
	}
	
	/**
	 * Perform an internal request
	 * @param string $s_requestHash The hash containing the action to perform
	 * @param array $a_options Array with options
	 */
	public function internalRequest(string $s_requestHash, array $a_options = []) {
		throw new \Reef\Exception\InvalidArgumentException('Field does not implement internal requests');
	}
}
