<?php

namespace Reef\Components;

use Reef\Locale\Trait_FieldLocale;
use Reef\Form\Form;
use Symfony\Component\Yaml\Yaml;
use \Reef\Components\Traits\Hidable\HidableFieldInterface;
use \Reef\Components\Traits\Hidable\HidableFieldTrait;

/**
 * A field is an instance of a component in a form
 */
abstract class Field implements HidableFieldInterface {
	
	use Trait_FieldLocale;
	use HidableFieldTrait;
	
	/**
	 * The field declaration
	 * @type array
	 */
	protected $a_declaration;
	
	/**
	 * The component object this field belongs to
	 * @type Component
	 */
	protected $Component;
	
	/**
	 * The form this field belongs to
	 * @type Form
	 */
	protected $Form;
	
	/**
	 * Constructor
	 * @param array $a_declaration The field declaration
	 * @param Form $Form The form this field belongs to
	 * @param Component $Component The component object
	 */
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
		return $this->validateDeclaration_hidable($a_errors);
	}
	
	/**
	 * Return a new value for this field
	 * @param \Reef\Submission\Submission $Submission The submission the value will belong to
	 * @return FieldValue
	 */
	public function newValue(\Reef\Submission\Submission $Submission) : FieldValue {
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
	
	/**
	 * Determine whether this field holds a value
	 * @return bool
	 */
	public function hasValue() {
		return !empty($this->getFlatStructure());
	}
	
	/**
	 * Retrieve the flat structure of the columns, by column name
	 * @return array[] Field structure data arrays indexed by column name
	 */
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
	
	/**
	 * Compute a mapping from data field names to column names
	 * @return string[] Column names indexed by data field names
	 */
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
	
	/**
	 * Compute a mapping from column names to data field names
	 * @return string[] Data field names indexed by column names
	 */
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
	 * Perform data updates before the field is removed from the form. Only called
	 * when the form keeps existing without this field, not when the entire form is
	 * deleted.
	 * @param array $a_data Array containing:
	 *   - storageFactoryName : The name of the used storage factory
	 *   - content_updater : A function that can be used to perform SQL queries
	 *   - columns : The old column names
	 */
	public function beforeDelete($a_data) {
	}
	
	/**
	 * Perform data updates after the schema is changed
	 * @param array $a_data Array containing:
	 *   - storageFactoryName : The name of the used storage factory
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
	 * @param FieldValue $Value The value object
	 * @param array $a_options Options
	 * @return array The template variables
	 */
	public function view_form(FieldValue $Value, $a_options = []) : array {
		$a_vars = $this->a_declaration;
		
		// Merge generalized options
		$a_vars = array_merge($a_vars, $this->view_form_hidable($Value));
		
		if($this instanceof \Reef\Components\Traits\Required\RequiredFieldInterface) {
			$a_vars = array_merge($a_vars, $this->view_form_required($Value));
		}
		
		$a_vars['errors'] = $Value->getErrors();
		$a_vars['hasErrors'] = !empty($a_vars['errors']);
		
		$a_vars['locale'] = $this->getLocale($a_options['locale']??null);
		unset($a_vars['locales']);
		
		return $a_vars;
	}
	
	/**
	 * Build template variables for the submission
	 * @param FieldValue $Value The value object
	 * @param array $a_options Options
	 * @return array The template variables
	 */
	public function view_submission(FieldValue $Value, $a_options = []) : array {
		$a_vars = $this->a_declaration;
		
		$a_vars['locale'] = $this->getLocale($a_options['locale']??null);
		unset($a_vars['locales']);
		
		return $a_vars;
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
