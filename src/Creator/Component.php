<?php

namespace Reef\Creator;

use \Reef\Exception\CreatorException;

class Component extends Context {
	
	private $i_index = null;
	private $a_fields;
	private $a_name2index = [];
	private $a_fieldRenames = [];
	
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
	
	public function getForm() : Form {
		$this->i_index = null;
		return $this->Creator->getForm();
	}
	
	public function _getFieldRenames() : array {
		return $this->a_fieldRenames;
	}
	
	public function _resetFieldRenames() {
		$this->a_fieldRenames = [];
	}
	
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
	
	public function getFieldByName(string $s_fieldName) : Component {
		$this->i_index = null;
		
		$this->checkFieldName($s_fieldName);
		
		$this->i_index = $this->a_name2index[$s_fieldName];
		
		return $this;
	}
	
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
	
	public function getFieldByPosition(int $i_position) : Component {
		return $this->getFieldByIndex($i_position - 1);
	}
	
	public function getFirstField() : Component {
		return $this->getFieldByIndex(0);
	}
	
	public function getLastField() : Component {
		return $this->getFieldByIndex(-1);
	}
	
	public function getNextField() : Component {
		$this->requireIndex();
		return $this->getFieldByIndex($this->i_index + 1);
	}
	
	public function getPrevField() : Component {
		$this->requireIndex();
		return $this->getFieldByIndex($this->i_index - 1);
	}
	
	private function requireIndex() {
		if($this->i_index === null) {
			throw new CreatorException('No field selected');
		}
	}
	
	private function checkFieldName(string $s_fieldName) {
		if(!isset($this->a_name2index[$s_fieldName])) {
			throw new CreatorException('Field "'.$s_fieldName.'" does not exist');
		}
	}
	
	public function getIndex(?int &$i_index) : Component {
		$this->requireIndex();
		$i_index = $this->i_index;
		return $this;
	}
	
	public function returnIndex() {
		$this->requireIndex();
		return $this->i_index;
	}
	
	public function getPosition(?int &$i_position) : Component {
		$this->requireIndex();
		$i_position = $this->i_index + 1;
		return $this;
	}
	
	public function returnPosition() {
		$this->requireIndex();
		return $this->i_index + 1;
	}
	
	public function get(string $s_key, &$m_value) : Component {
		$this->requireIndex();
		$m_value = $this->a_fields[$this->i_index][$s_key]??null;
		return $this;
	}
	
	public function return(string $s_key) {
		$this->requireIndex();
		return $this->a_fields[$this->i_index][$s_key]??null;
	}
	
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
	
	public function set(string $s_key, $m_value) : Component {
		if($s_key == 'name') {
			return $this->setName($m_value);
		}
		
		$this->requireIndex();
		
		$a_field = &$this->a_fields[$this->i_index];
		
		$a_field[$s_key] = $m_value;
		
		return $this;
	}
	
	public function delete() : Creator {
		$this->requireIndex();
		
		array_splice($this->a_fields, $this->i_index, 1);
		
		$this->i_index = null;
		return $this->Creator;
	}
	
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
		
		$a_field = &$this->a_fields[$this->i_index];
		
		array_splice($this->a_fields, $i_index, 0, array_splice($this->a_fields, $this->i_index, 1));
		
		for($i=min($i_index, $this->i_index); $i<=max($i_index, $this->i_index); $i++) {
			if(isset($this->a_fields[$i]['name'])) {
				$this->a_name2index[$this->a_fields[$i]['name']] = $i;
			}
		}
		
		$this->i_index = $i_index;
		
		return $this;
	}
	
	public function setPosition(int $i_position) : Component {
		return $this->setIndex($i_position - 1);
	}
	
	public function moveAfter(string $s_fieldName) : Component {
		$this->requireIndex();
		
		$this->checkFieldName($s_fieldName);
		
		$i_newIndex = $this->a_name2index[$s_fieldName];
		if($this->i_index > $i_newIndex) {
			$i_newIndex++;
		}
		
		return $this->setIndex($i_newIndex);
	}
	
	public function moveBefore(string $s_fieldName) : Component {
		$this->requireIndex();
		
		$this->checkFieldName($s_fieldName);
		
		$i_newIndex = $this->a_name2index[$s_fieldName];
		if($this->i_index < $i_newIndex) {
			$i_newIndex--;
		}
		
		return $this->setIndex($i_newIndex);
	}
	
	public function moveUp() : Component {
		$this->requireIndex();
		return $this->setIndex($this->i_index - 1);
	}
	
	public function moveDown() : Component {
		$this->requireIndex();
		return $this->setIndex($this->i_index + 1);
	}
	
	public function moveToBegin() : Component {
		return $this->setIndex(0);
	}
	
	public function moveToEnd() : Component {
		return $this->setIndex(-1);
	}
	
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
