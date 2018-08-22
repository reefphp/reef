<?php

namespace Reef\Components;

use Reef\Locale\Trait_FieldLocale;
use Reef\Form\Form;
use Symfony\Component\Yaml\Yaml;

abstract class Field {
	
	use Trait_FieldLocale;
	
	protected $a_declaration;
	protected $Component;
	protected $Form;
	
	public function __construct(array $a_declaration, Form $Form, Component $Component) {
		$this->a_declaration = $a_declaration;
		$this->Form = $Form;
		$this->Component = $Component;
	}
	
	/**
	 * Return the Form the field is assigned to
	 * @return Form
	 */
	public function getForm() : Form {
		return $this->Form;
	}
	
	/**
	 * Return the Component of this Field
	 * @return Component
	 */
	public function getComponent() : Component {
		return $this->Component;
	}
	
	/**
	 * Return the entire declaration array
	 * @return array
	 */
	public function getDeclaration() : array {
		return $this->a_declaration;
	}
	
	/**
	 * Perform validation on a declaration, additional to the default form validation
	 * @param array &$a_errors Array of errors
	 * @return bool True if valid
	 */
	public function validateDeclaration(array &$a_errors = null) : bool {
		return true;
	}
	
	/**
	 * Return a new value for this field
	 * @param \Reef\Submission $Submission The submission the value will belong to
	 * @return FieldValue
	 */
	public function newValue(\Reef\Submission $Submission) : FieldValue {
		$s_class = substr(static::class, 0, -5).'Value';
		return new $s_class($Submission, $this);
	}
	
	/**
	 * Obtain information on how this field should be saved in a flat structure
	 * 
	 * This function should return an array of definition arrays. Each definition array defines the type of
	 * one data field in the stored resultset of a submission. The returned array SHOULD be an associative
	 * array if it contains more than one definition array. The keys of the associative array will serve as
	 * identifiers for the data fields in the resultset. Only in case the array consists of only one definition
	 * array, it is allowed to skip the key (effectively setting it to 0). In this case, the key will not be
	 * used in the data field name.
	 * 
	 * The keys returned by the corresponding Value::toFlat() MUST match the keys returned by this function.
	 * 
	 * A structure defining array must include a 'type' value, being one of the \Reef\Storage\Storage::TYPE_*
	 * values. Each such type value may also require other settings to be defined
	 * 
	 * @return array The flat structure information
	 */
	abstract public function getFlatStructure() : array;
	
	public function hasValue() {
		return !empty($this->getFlatStructure());
	}
	
	final public function getFlatStructureByColumnName() : array {
		$s_name = $this->getDeclaration()['name'];
		
		$a_fieldStructure = $this->getFlatStructure();
		$a_columnStructure = [];
		
		if(count($a_fieldStructure) == 1 && \Reef\array_first_key($a_fieldStructure) === 0) {
			$a_columnStructure[$s_name] = $a_fieldStructure[0];
		}
		else {
			foreach($a_fieldStructure as $s_dataFieldName => $a_dataFieldStructure) {
				$a_columnStructure[$s_name.'__'.$s_dataFieldName] = $a_dataFieldStructure;
			}
		}
		
		return $a_columnStructure;
	}
	
	final public function dataFieldNamesToColumnNames() : array {
		$s_name = $this->getDeclaration()['name'];
		$a_fieldStructure = $this->getFlatStructure();
		
		$a_fieldNames = [];
		
		if(count($a_fieldStructure) == 1 && \Reef\array_first_key($a_fieldStructure) === 0) {
			$a_fieldNames[0] = $s_name;
		}
		else {
			foreach($a_fieldStructure as $s_dataFieldName => $a_dataFieldStructure) {
				$a_fieldNames[$s_dataFieldName] = $s_name.'__'.$s_dataFieldName;
			}
		}
		return $a_fieldNames;
	}
	
	final public function columnNamesToDataFieldNames() : array {
		$s_name = $this->getDeclaration()['name'];
		$a_fieldStructure = $this->getFlatStructure();
		
		$a_columnNames = [];
		
		if(count($a_fieldStructure) == 1 && \Reef\array_first_key($a_fieldStructure) === 0) {
			$a_columnNames[$s_name] = 0;
		}
		else {
			foreach($a_fieldStructure as $s_dataFieldName => $a_dataFieldStructure) {
				$a_columnNames[$s_name.'__'.$s_dataFieldName] = $s_dataFieldName;
			}
		}
		return $a_columnNames;
	}
	
	/**
	 * Determine whether a schema update is required for this field.
	 * The before/after schema update functions will be called whenever:
	 *   - the flat structure changes, or
	 *   - the field name changes, or
	 *   - this function returns true
	 * May throw a ValidationException to indicate incompatibilities
	 * @param Field $OldField The old field migrating from
	 * @return boolean Whether a schema update is required
	 */
	public function needsSchemaUpdate(Field $OldField) {
		return false;
	}
	
	/**
	 * Perform data updates before the schema is changed
	 * @param array $a_data The same array as in afterSchemaUpdate(), with in addition:
	 *   - new_field : The new field we are migrating to
	 */
	public function beforeSchemaUpdate($a_data) {
	}
	
	
	/**
	 * Perform data updates after the schema is changed
	 * @param array $a_data Array containing:
	 *   - PDO_DRIVER : The PDO driver used
	 *   - content_updater : A function that can be used to perform SQL queries
	 *   - old_columns : The old column names
	 *   - new_columns : The new column names
	 */
	public function afterSchemaUpdate($a_data) {
	}
	
	/**
	 * Determine whether a field update would lead to data loss
	 * @param Field $OldField The old field
	 * @return string One of the Updater::DATALOSS_* constants
	 */
	public function updateDataLoss(Field $OldField) {
		return \Reef\Updater::DATALOSS_POTENTIAL;
	}
	
	/**
	 * Define which columns this field will contribute in an overview (CSV) table
	 * @return array The column names
	 */
	abstract public function getOverviewColumns() : array;
	
	/**
	 * Build template variables for the form
	 * @param ?FieldValue $Value The value object, may be null for static components
	 * @param array $a_options Options
	 * @return array The template variables
	 */
	public function view_form(?FieldValue $Value, $a_options = []) : array {
		$a_vars = $this->a_declaration;
		
		// Merge generalized options
		if($this instanceof \Reef\Components\Traits\Required\RequiredFieldInterface) {
			$a_vars = array_merge($a_vars, $this->view_form_required($Value));
		}
		
		$a_vars['errors'] = !empty($Value) ? $Value->getErrors() : [];
		$a_vars['hasErrors'] = !empty($a_vars['errors']);
		
		$a_vars['locale'] = $this->getLocale($a_options['locale']??null);
		unset($a_vars['locales']);
		
		return $a_vars;
	}
	
	/**
	 * Build template variables for the submission
	 * @param ?FieldValue $Value The value object, may be null for static components
	 * @param array $a_options Options
	 * @return array The template variables
	 */
	public function view_submission(?FieldValue $Value, $a_options = []) : array {
		$a_vars = $this->a_declaration;
		
		$a_vars['locale'] = $this->getLocale($a_options['locale']??null);
		unset($a_vars['locales']);
		
		return $a_vars;
	}
}
