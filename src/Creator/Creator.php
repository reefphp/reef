<?php

namespace Reef\Creator;

class Creator extends Context {
	
	protected $Form;
	protected $Component;
	
	protected $FormObject;
	
	public function __construct(\Reef\Form\Form $Form) {
		$this->FormObject = $Form;
		
		$a_definition = $Form->generateDefinition();
		$a_definition['fields'] = array_values($a_definition['fields']);
		
		parent::__construct($this, $a_definition);
		
		$this->Form = new Form($this, $this->a_definition);
		$this->Component = new Component($this, $this->a_definition);
	}
	
	public function getFormObject() : \Reef\Form\Form {
		return $this->FormObject;
	}
	
	public function getForm() : Form {
		return $this->Form;
	}
	
	public function _getComponent() : Component {
		return $this->Component;
	}
	
	public function apply() : Creator {
		$this->FormObject->updateDefinition($this->a_definition, $this->Component->_getFieldRenames());
		
		$this->Component->_resetFieldRenames();
		
		return $this;
	}
	
}
