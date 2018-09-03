<?php

namespace Reef;

use \Reef\Form\Form;
use \Reef\Exception\OutOfBoundsException;

/**
 * A submission is a single list of values submitted to a form. The values
 * are encoded as FieldValue objects of the respective Field objects present
 * in the Form.
 * 
 * A submission can be saved to a structured form, which may be a multi-dimensional
 * array, or to a flat form, which is a one-dimensional 'flattened' representation of
 * the multi-dimensional form. The flat form is used for saving to the database, while
 * the structured form is probably better suited for some more programmatic processing.
 */
abstract class Submission {
	
	/**
	 * The form to which this submission belongs
	 * @type Form
	 */
	protected $Form;
	
	/**
	 * Unique id
	 * @type string
	 */
	protected $s_uuid;
	
	/**
	 * The FieldValue objects of the submission, indexed by field name
	 * @type FieldValue[]
	 */
	protected $a_fieldValues = [];
	
	/**
	 * Constructor
	 * @param Form $Form The Form this submission belongs to
	 */
	public function __construct(Form $Form) {
		$this->Form = $Form;
		$this->s_uuid = \Reef\unique_id();
	}
	
	/**
	 * Get the uuid of this submission
	 * 
	 * @return string
	 */
	public function getUUID() {
		return $this->s_uuid;
	}
	
	/**
	 * Get the form this submission belongs to
	 * 
	 * @return Form
	 */
	public function getForm() {
		return $this->Form;
	}
	
	/**
	 * Evaluate a condition on this submission
	 * 
	 * @param string $s_condition The condition to evaluate
	 * 
	 * @return ?bool Null if the condition is empty, the boolean result otherwise
	 * 
	 * @throws \Reef\Exception\ConditionException If the input condition is invalid
	 */
	public function evaluateCondition(string $s_condition) : ?bool {
		return $this->Form->getConditionEvaluator()->evaluate($this, $s_condition);
	}
	
	/**
	 * Load default values into this submission
	 */
	public function emptySubmission() {
		$a_fields = $this->Form->getValueFields();
		$this->a_fieldValues = [];
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$this->a_fieldValues[$s_name] = $Field->newValue($this);
			$this->a_fieldValues[$s_name]->fromDefault();
		}
	}
	
	/**
	 * Load values from user input
	 * @param array $a_input The user input data
	 */
	public function fromUserInput($a_input) {
		if(empty($this->a_fieldValues)) {
			$this->emptySubmission();
		}
		
		$a_fields = $this->Form->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$this->a_fieldValues[$s_name]->fromUserInput($a_input[$s_name] ?? null);
		}
	}
	
	/**
	 * Load values from structured input
	 * @param array $a_structured The structured input data
	 */
	public function fromStructured($a_structured) {
		$a_fields = $this->Form->getValueFields();
		$this->a_fieldValues = [];
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$this->a_fieldValues[$s_name] = $Field->newValue($this);
			if(array_key_exists($s_name, $a_structured)) {
				$this->a_fieldValues[$s_name]->fromStructured($a_structured[$s_name]);
			}
			else {
				$this->a_fieldValues[$s_name]->fromDefault();
			}
		}
	}
	
	/**
	 * Validate the submission. After this, errors can be fetch with getErrors()
	 * @return bool True if all submitted values are valid, false otherwise
	 */
	public function validate() : bool {
		$b_valid = true;
		
		$a_fields = $this->Form->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$b_valid = $this->a_fieldValues[$s_name]->validate() && $b_valid;
		}
		
		return $b_valid;
	}
	
	/**
	 * Obtain validation errors. Available after calling validate()
	 * @return string[][] Array of errors, being an array of string[] errors indexed by field names
	 */
	public function getErrors() : array {
		$a_errors = [];
		
		$a_fields = $this->Form->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$a_fieldErrors = $this->a_fieldValues[$s_name]->getErrors();
			if(!empty($a_fieldErrors)) {
				$a_errors[$s_name] = $a_fieldErrors;
			}
		}
		
		return $a_errors;
	}
	
	/**
	 * Determine whether this submission contains a value for the specified field
	 * @return bool True if the field name is present
	 */
	public function hasField($s_name) {
		return isset($this->a_fieldValues[$s_name]);
	}
	
	/**
	 * Get the FieldValue of the specified field
	 * @return FieldValue
	 * @throws OutOfBoundsException If the specified field does not exist
	 */
	public function getFieldValue($s_name) {
		if(!isset($this->a_fieldValues[$s_name])) {
			throw new OutOfBoundsException("Could not find value for field '".$s_name."'.");
		}
		
		return $this->a_fieldValues[$s_name];
	}
	
	/**
	 * Translate the submission into flat format
	 * @return scalar[]
	 */
	public function toFlat() {
		$a_flat = [];
		
		$a_fields = $this->Form->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$a_flatStructure = $Field->getFlatStructure();
			
			$a_flatField = $this->a_fieldValues[$s_name]->toFlat();
			$a_prefixedFlatField = [];
			
			if(count($a_flatStructure) == 1 && \Reef\array_first_key($a_flatStructure) === 0) {
				$a_prefixedFlatField[$s_name] = reset($a_flatField);
			}
			else {
				foreach($a_flatStructure as $s_dataFieldName => $a_dataFieldStructure) {
					$a_prefixedFlatField[$s_name.'__'.$s_dataFieldName] = $a_flatField[$s_dataFieldName] ?? null;
				}
			}
			
			$a_flat = array_merge($a_flat, $a_prefixedFlatField);
		}
		
		return $a_flat;
	}
	
	/**
	 * Load values from flat input (e.g. from the database)
	 * @param scalar[] $a_flat The flat input data
	 */
	public function fromFlat($a_flat) {
		
		$a_fields = $this->Form->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$a_flatStructure = $Field->getFlatStructure();
			
			if(count($a_flatStructure) == 1 && \Reef\array_first_key($a_flatStructure) === 0) {
				$a_flatField = [
					$a_flat[$s_name]
				];
			}
			else {
				$a_flatField = [];
				foreach($a_flatStructure as $s_dataFieldName => $a_dataFieldStructure) {
					$a_flatField[$s_dataFieldName] = $a_flat[$s_name.'__'.$s_dataFieldName] ?? null;
				}
			}
			
			$this->a_fieldValues[$s_name] = $Field->newValue($this);
			$this->a_fieldValues[$s_name]->fromFlat($a_flatField);
		}
	}
	
	/**
	 * Translate the submission into structured format
	 * @return array
	 */
	public function toStructured($a_options = []) {
		$a_data = [];
		
		$a_fields = $this->Form->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			if(!empty($a_options['skip_default']) && $this->a_fieldValues[$s_name]->isDefault()) {
				continue;
			}
			$a_data[$s_name] = $this->a_fieldValues[$s_name]->toStructured();
		}
		
		return $a_data;
	}
	
	/**
	 * Translate the submission into a flat format for use in overviews
	 * @return scalar[]
	 */
	public function toOverviewColumns() {
		$a_overviewColumns = [];
		
		$a_fields = $this->Form->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$a_columns = $Field->getOverviewColumns();
			
			$a_fieldColumnValues = $this->a_fieldValues[$s_name]->toOverviewColumns();
			
			foreach($a_columns as $s_colName => $s_colTitle) {
				$a_overviewColumns[$s_name.'__'.$s_colName] = $a_fieldColumnValues[$s_colName] ?? null;
			}
			
		}
		
		return $a_overviewColumns;
	}
	
	/**
	 * Perform an internal request
	 * @param string $s_requestHash The hash containing the action to perform
	 * @param array $a_options Array with options
	 */
	public function internalRequest(string $s_requestHash, array $a_options = []) {
		$a_requestHash = explode(':', $s_requestHash);
		if(count($a_requestHash) == 1) {
			throw new \Reef\Exception\InvalidArgumentException("Illegal request hash");
		}
		
		if($a_requestHash[0] == 'field') {
			
			if(!$this->hasField($a_requestHash[1])) {
				throw new \Reef\Exception\InvalidArgumentException("Could not find field '".$a_requestHash[1]."'");
			}
			
			$Value = $this->getFieldValue($a_requestHash[1]);
			
			array_shift($a_requestHash);
			array_shift($a_requestHash);
			
			return $Value->internalRequest(implode(':', $a_requestHash), $a_options);
		}
		
		throw new \Reef\Exception\InvalidArgumentException('Invalid request hash');
	}
}
