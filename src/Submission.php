<?php

namespace Reef;

use \Reef\Form\Form;
use \Reef\Exception\OutOfBoundsException;

abstract class Submission {
	
	protected $Form;
	
	protected $a_fieldValues = [];
	
	/**
	 * Constructor
	 */
	public function __construct(Form $Form) {
		$this->Form = $Form;
	}
	
	public function getForm() {
		return $this->Form;
	}
	
	public function evaluateCondition(string $s_condition) : ?bool {
		return $this->Form->getConditionEvaluator()->evaluate($this, $s_condition);
	}
	
	public function emptySubmission() {
		$a_fields = $this->Form->getValueFields();
		$this->a_fieldValues = [];
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$this->a_fieldValues[$s_name] = $Field->newValue($this);
			$this->a_fieldValues[$s_name]->fromDefault();
		}
	}
	
	public function fromUserInput($a_input) {
		$a_fields = $this->Form->getValueFields();
		$this->a_fieldValues = [];
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$this->a_fieldValues[$s_name] = $Field->newValue($this);
			$this->a_fieldValues[$s_name]->fromUserInput($a_input[$s_name] ?? null);
		}
	}
	
	public function fromStructured($a_structured) {
		$a_fields = $this->Form->getValueFields();
		$this->a_fieldValues = [];
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$this->a_fieldValues[$s_name] = $Field->newValue($this);
			if(isset($a_structured[$s_name]) || $Field->isRequired()) {
				$this->a_fieldValues[$s_name]->fromStructured($a_structured[$s_name]??null);
			}
			else {
				$this->a_fieldValues[$s_name]->fromDefault();
			}
		}
	}
	
	public function validate() : bool {
		$b_valid = true;
		
		$a_fields = $this->Form->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$b_valid = $this->a_fieldValues[$s_name]->validate() && $b_valid;
		}
		
		return $b_valid;
	}
	
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
	
	public function hasField($s_name) {
		return isset($this->a_fieldValues[$s_name]);
	}
	
	public function getFieldValue($s_name) {
		if(!isset($this->a_fieldValues[$s_name])) {
			throw new OutOfBoundsException("Could not find value for field '".$s_name."'.");
		}
		
		return $this->a_fieldValues[$s_name];
	}
	
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
	
	public function toStructured($a_options = []) {
		$a_data = [];
		
		$a_fields = $this->Form->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			if(!empty($a_options['skip_default']) && !$Field->isRequired() && $this->a_fieldValues[$s_name]->isDefault()) {
				continue;
			}
			$a_data[$s_name] = $this->a_fieldValues[$s_name]->toStructured();
		}
		
		return $a_data;
	}
	
	public function toOverviewColumns() {
		$a_overviewColumns = [];
		
		$a_fields = $this->Form->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$a_columns = $Field->getOverviewColumns();
			
			$a_flatField = $this->a_fieldValues[$s_name]->toOverviewColumns();
			
			foreach($a_columns as $s_colName => $s_colTitle) {
				$a_overviewColumns[$s_name.'__'.$s_colName] = $a_flatField[$s_colName] ?? null;
			}
			
		}
		
		return $a_overviewColumns;
	}
}
