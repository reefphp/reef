<?php

namespace Reef\Creator;

use \Reef\Exception\CreatorException;

/**
 * Functionality for editing a field declaration in the form definition.
 * Fields can be fetched in three ways:
 *  - By their field name (for fields having a name)
 *  - By their index: 0-based indexing in the fields array
 *  - By their position: 1-based indexing in the fields array
 */
class Component extends Context {
	
	/**
	 * The index of the field we are currently working on. If null,
	 * no index is selected and no modifications can be done.
	 * @type ?int
	 */
	private $i_index = null;
	
	/**
	 * The fields section of the form definition
	 * @type array
	 */
	private $a_fields;
	
	/**
	 * Mapping from field names to field indices
	 * @type int[]
	 */
	private $a_name2index = [];
	
	/**
	 * Array tracking field renames to be used when applying the changes,
	 * mapping old field names to new field names
	 * @type string[]
	 */
	private $a_fieldRenames = [];
	
	/**
	 * Constructor
	 * @param \Reef\Creator\Creator $Creator The Creator object
	 * @param array &$a_definition The form definition the creator works on
	 */
	public function __construct(Creator $Creator, array &$a_definition) {
		parent::__construct($Creator, $a_definition);
		
		if(!isset($this->a_definition['fields'])) {
			// @codeCoverageIgnoreStart
			$this->a_definition['fields'] = [];
			// @codeCoverageIgnoreEnd
		}
		
		$this->a_fields = &$this->a_definition['fields'];
		
		foreach($this->a_fields as $i_index => $a_field) {
			if(isset($a_field['name'])) {
				$this->a_name2index[$a_field['name']] = $i_index;
			}
		}
	}
	
	/**
	 * @inherit
	 */
	public function getForm() : Form {
		$this->i_index = null;
		return $this->Creator->getForm();
	}
	
	/**
	 * (Internal) Get the field renames array
	 * @return string[]
	 */
	public function _getFieldRenames() : array {
		return $this->a_fieldRenames;
	}
	
	/**
	 * (Internal) Reset the field renames array
	 */
	public function _resetFieldRenames() {
		$this->a_fieldRenames = [];
	}
	
	/**
	 * Append a new field to the end of the form and select it for editing
	 * @param string $s_componentName The name of the component to use
	 * @return $this
	 */
	public function addField(string $s_componentName) : Component {
		$this->i_index = null;
		
		if(!$this->Reef->getSetup()->hasComponent($s_componentName)) {
			throw new CreatorException('Component "'.$s_componentName.'" does not exist');
		}
		
		$this->i_index = count($this->a_fields);
		$this->a_fields[] = [
			'component' => $s_componentName,
		];
		
		return $this;
	}
	
	/**
	 * Select a field for editing, by name
	 * @param string $s_fieldName The name of the field to select
	 * @return $this
	 */
	public function getFieldByName(string $s_fieldName) : Component {
		$this->i_index = null;
		
		$this->checkFieldName($s_fieldName);
		
		$this->i_index = $this->a_name2index[$s_fieldName];
		
		return $this;
	}
	
	/**
	 * Select a field for editing, by index
	 * @param int $i_index The index of the field to select
	 * @return $this
	 */
	public function getFieldByIndex(int $i_index) : Component {
		$this->i_index = null;
		
		if($i_index < 0) {
			$i_index = count($this->a_fields) + $i_index;
		}
		
		if(!(0 <= $i_index && $i_index < count($this->a_fields))) {
			throw new CreatorException('Invalid field index "'.$i_index.'"');
		}
		
		$this->i_index = $i_index;
		
		return $this;
	}
	
	/**
	 * Select a field for editing, by position
	 * @param int $i_position The position of the field to select
	 * @return $this
	 */
	public function getFieldByPosition(int $i_position) : Component {
		return $this->getFieldByIndex($i_position - 1);
	}
	
	/**
	 * Select the first field for editing
	 * @return $this
	 */
	public function getFirstField() : Component {
		return $this->getFieldByIndex(0);
	}
	
	/**
	 * Select the last field for editing
	 * @return $this
	 */
	public function getLastField() : Component {
		return $this->getFieldByIndex(-1);
	}
	
	/**
	 * Select the next field for editing
	 * @return $this
	 */
	public function getNextField() : Component {
		$this->requireIndex();
		return $this->getFieldByIndex($this->i_index + 1);
	}
	
	/**
	 * Select the previous field for editing
	 * @return $this
	 */
	public function getPrevField() : Component {
		$this->requireIndex();
		return $this->getFieldByIndex($this->i_index - 1);
	}
	
	/**
	 * Ensure a field is selected for editing
	 * @throws CreatorException If the index is null
	 */
	private function requireIndex() {
		if($this->i_index === null) {
			throw new CreatorException('No field selected');
		}
	}
	
	/**
	 * Ensure a field name exists
	 * @throws CreatorException If the field name does not exist
	 */
	private function checkFieldName(string $s_fieldName) {
		if(!isset($this->a_name2index[$s_fieldName])) {
			throw new CreatorException('Field "'.$s_fieldName.'" does not exist');
		}
	}
	
	/**
	 * Get the index of the currently selected field
	 * @param ?int &$i_index (Out) The index
	 * @return $this
	 */
	public function getIndex(?int &$i_index) : Component {
		$this->requireIndex();
		$i_index = $this->i_index;
		return $this;
	}
	
	/**
	 * Return the index of the currently selected field
	 * @return ?int The index
	 */
	public function returnIndex() {
		$this->requireIndex();
		return $this->i_index;
	}
	
	/**
	 * Get the position of the currently selected field
	 * @param ?int &$i_position (Out) The position
	 * @return $this
	 */
	public function getPosition(?int &$i_position) : Component {
		$this->requireIndex();
		$i_position = $this->i_index + 1;
		return $this;
	}
	
	/**
	 * Return the position of the currently selected field
	 * @return ?int The position
	 */
	public function returnPosition() {
		$this->requireIndex();
		return $this->i_index + 1;
	}
	
	/**
	 * Get the value of a field property in the currently selected field
	 * @param string $s_key The field property key
	 * @param mixed &$m_value (Out) The property value, or null if it is not set
	 * @return $this
	 */
	public function get(string $s_key, &$m_value) : Component {
		$this->requireIndex();
		$m_value = $this->a_fields[$this->i_index][$s_key]??null;
		return $this;
	}
	
	/**
	 * Return the value of a field property in the currently selected field
	 * @param string $s_key The field property key
	 * @return mixed The property value, or null if it is not set
	 */
	public function return(string $s_key) {
		$this->requireIndex();
		return $this->a_fields[$this->i_index][$s_key]??null;
	}
	
	/**
	 * Set the name of the currently selected field
	 * @param string $s_fieldName The new field name
	 * @return $this
	 * @throws CreatorException If the field name is already in use
	 */
	public function setName(string $s_fieldName) : Component {
		$this->requireIndex();
		
		if(isset($this->a_name2index[$s_fieldName])) {
			throw new CreatorException('Field with name "'.$s_fieldName.'" already exists');
		}
		
		$a_field = &$this->a_fields[$this->i_index];
		
		if(isset($a_field['name'])) {
			$s_oldFieldName = $a_field['name'];
			unset($this->a_name2index[$s_oldFieldName]);
			
			if(($s_originalFieldName = array_search($s_oldFieldName, $this->a_fieldRenames)) === false) {
				$this->a_fieldRenames[$s_oldFieldName] = $s_fieldName;
			}
			else {
				$this->a_fieldRenames[$s_originalFieldName] = $s_fieldName;
			}
		}
		
		$a_field['name'] = $s_fieldName;
		
		$this->a_name2index[$s_fieldName] = $this->i_index;
		
		return $this;
	}
	
	/**
	 * Set the value of a field property in the currently selected field
	 * @param string $s_key The field property key
	 * @param mixed $m_value The property value
	 * @return $this
	 */
	public function set(string $s_key, $m_value) : Component {
		if($s_key == 'name') {
			return $this->setName($m_value);
		}
		
		$this->requireIndex();
		
		$a_field = &$this->a_fields[$this->i_index];
		
		$a_field[$s_key] = $m_value;
		
		return $this;
	}
	
	/**
	 * Delete the currently selected field
	 * @return \Reef\Creator\Creator
	 */
	public function delete() : Creator {
		$this->requireIndex();
		
		array_splice($this->a_fields, $this->i_index, 1);
		
		$this->i_index = null;
		return $this->Creator;
	}
	
	/**
	 * Move the currently selected field to the given index
	 * @param int $i_index The new index
	 * @return $this
	 * @throws CreatorException If the new index is invalid
	 */
	public function setIndex(int $i_index) : Component {
		$this->requireIndex();
		
		if($i_index < 0) {
			$i_index = count($this->a_fields) + $i_index;
		}
		
		if($this->i_index == $i_index) {
			return $this;
		}
		
		if(!(0 <= $i_index && $i_index < count($this->a_fields))) {
			throw new CreatorException('Invalid field index "'.$i_index.'"');
		}
		
		array_splice($this->a_fields, $i_index, 0, array_splice($this->a_fields, $this->i_index, 1));
		
		for($i=min($i_index, $this->i_index); $i<=max($i_index, $this->i_index); $i++) {
			if(isset($this->a_fields[$i]['name'])) {
				$this->a_name2index[$this->a_fields[$i]['name']] = $i;
			}
		}
		
		$this->i_index = $i_index;
		
		return $this;
	}
	
	/**
	 * Move the currently selected field to the given position
	 * @param int $i_index The new position
	 * @return $this
	 */
	public function setPosition(int $i_position) : Component {
		return $this->setIndex($i_position - 1);
	}
	
	/**
	 * Move the currently selected field after another field by name
	 * @param string $s_fieldName The field name to move the field after
	 * @return $this
	 */
	public function moveAfter(string $s_fieldName) : Component {
		$this->requireIndex();
		
		$this->checkFieldName($s_fieldName);
		
		$i_newIndex = $this->a_name2index[$s_fieldName];
		if($this->i_index > $i_newIndex) {
			$i_newIndex++;
		}
		
		return $this->setIndex($i_newIndex);
	}
	
	/**
	 * Move the currently selected field before another field by name
	 * @param string $s_fieldName The field name to move the field before
	 * @return $this
	 */
	public function moveBefore(string $s_fieldName) : Component {
		$this->requireIndex();
		
		$this->checkFieldName($s_fieldName);
		
		$i_newIndex = $this->a_name2index[$s_fieldName];
		if($this->i_index < $i_newIndex) {
			$i_newIndex--;
		}
		
		return $this->setIndex($i_newIndex);
	}
	
	/**
	 * Move the currently selected field up one position
	 * @return $this
	 */
	public function moveUp() : Component {
		$this->requireIndex();
		return $this->setIndex($this->i_index - 1);
	}
	
	/**
	 * Move the currently selected field down one position
	 * @return $this
	 */
	public function moveDown() : Component {
		$this->requireIndex();
		return $this->setIndex($this->i_index + 1);
	}
	
	/**
	 * Move the currently selected field to the start of the field list
	 * @return $this
	 */
	public function moveToBegin() : Component {
		return $this->setIndex(0);
	}
	
	/**
	 * Move the currently selected field to the end of the field list
	 * @return $this
	 */
	public function moveToEnd() : Component {
		return $this->setIndex(-1);
	}
	
	/**
	 * Replace the locale in the currently selected field with new values
	 * @param ?string $s_locale The locale key to use, may be omitted to use the 'no locale'
	 * @param array $a_locale The locale array
	 * @return $this
	 */
	public function setLocale($s_locale, $a_locale = null) : Component {
		$this->requireIndex();
		
		if(func_num_args() == 1) {
			$a_locale = $s_locale;
			$s_locale = null;
		}
		
		$a_field = &$this->a_fields[$this->i_index];
		
		if($s_locale !== null) {
			$a_field['locales'][$s_locale] = $a_locale;
		}
		else {
			$a_field['locale'] = $a_locale;
		}
		
		return $this;
	}
	
	/**
	 * Add locale to the currently selected field
	 * @param ?string $s_locale The locale key to use, may be omitted to use the 'no locale'
	 * @param array $a_locale The locale array
	 * @return $this
	 */
	public function addLocale($s_locale, $a_locale = null) : Component {
		$this->requireIndex();
		
		if(func_num_args() == 1) {
			$a_locale = $s_locale;
			$s_locale = null;
		}
		
		$a_field = &$this->a_fields[$this->i_index];
		
		if($s_locale !== null) {
			$a_field['locales'][$s_locale] = array_merge($a_field['locales'][$s_locale]??[], $a_locale);
		}
		else {
			$a_field['locale'] = array_merge($a_field['locale']??[], $a_locale);
		}
		
		return $this;
	}
}
