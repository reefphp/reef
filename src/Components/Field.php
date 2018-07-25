<?php

namespace Reef\Components;

use Reef\Trait_Locale;
use Reef\Form;
use Symfony\Component\Yaml\Yaml;

abstract class Field {
	
	use Trait_Locale;
	
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
	
	public function beforeSchemaUpdate($a_data) {
	}
	
	public function afterSchemaUpdate($a_data) {
	}
	
	/**
	 * Determine whether a field update would require the value to be updated as well
	 * @param Field $OldField The old field
	 * @param ?bool $b_dataLoss (Out) Whether data loss may occur when updating the field, or null if it is unknown
	 * @return boolean Whether the value has to be updated
	 */
	public function needsValueUpdate(Field $OldField, ?bool &$b_dataLoss = null) : bool {
		return false;
	}
	
	/**
	 * Return whether this field is required
	 * @return bool
	 */
	public function isRequired() {
		return false;
	}
	
	/**
	 * Build template variables for the form builder
	 * @return array The template variables
	 */
	abstract public function view_builder() : array;
	
	/**
	 * Build template variables for the form
	 * @param ?FieldValue $Value The value object, may be null for static components
	 * @param array $a_options Options
	 * @return array The template variables
	 */
	public function view_form(?FieldValue $Value, $a_options = []) : array {
		$a_vars = $this->a_declaration;
		
		$a_vars['errors'] = !empty($Value) ? $Value->getErrors() : [];
		$a_vars['hasErrors'] = !empty($a_vars['errors']);
		
		$a_vars['locale'] = $this->getLocale($a_options['locale']??null);
		unset($a_vars['locales']);
		
		return $a_vars;
	}
	
	protected function fetchBaseLocale($s_locale) {
		if(!empty($s_locale) && isset($this->a_declaration['locales'][$s_locale])) {
			return $this->a_declaration['locales'][$s_locale];
		}
		else if(isset($this->a_declaration['locale'])) {
			return $this->a_declaration['locale'];
		}
		else {
			return [];
		}
	}
	
	protected function getLocaleKeys() {
		return array_keys($this->getComponent()->getConfiguration()['locale']);
	}
	
	public function getCombinedLocaleSources($s_locale) {
		return $this->combineLocaleSources(
			$this->getOwnLocaleSource($s_locale),
			$this->getForm()->getOwnLocaleSource($s_locale),
			$this->getComponent()->getCombinedLocaleSources($s_locale)
		);
	}
	
	protected function getDefaultLocale() {
		return $this->getForm()->getDefinition()['default_locale']??null;
	}
}
