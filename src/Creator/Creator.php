<?php

namespace Reef\Creator;

/**
 * The creator class is the entry point to the chaining interface for building form definitions.
 */
class Creator extends Context {
	
	/**
	 * The form creator context
	 * @type \Reef\Creator\Form
	 */
	protected $Form;
	
	/**
	 * The component creator context
	 * @type \Reef\Creator\Component
	 */
	protected $Component;
	
	/**
	 * The form this creator class edits
	 * @type \Reef\Form\Form
	 */
	protected $FormObject;
	
	/**
	 * Constructor
	 * @param \Reef\Form\Form $Form The form this creator class should edit
	 */
	public function __construct(\Reef\Form\Form $Form) {
		$this->FormObject = $Form;
		
		$a_definition = $Form->getDefinition();
		$a_definition['fields'] = array_values($a_definition['fields']);
		
		parent::__construct($this, $a_definition);
		
		$this->Form = new Form($this, $this->a_definition);
		$this->Component = new Component($this, $this->a_definition);
	}
	
	/**
	 * Get the form object this creator class edits
	 * @return \Reef\Form\Form
	 */
	public function getFormObject() : \Reef\Form\Form {
		return $this->FormObject;
	}
	
	/**
	 * @inherit
	 */
	public function getForm() : Form {
		return $this->Form;
	}
	
	/**
	 * (Internal) Get the component creator context
	 * @return \Reef\Creator\Component
	 */
	public function _getComponent() : Component {
		return $this->Component;
	}
	
	/**
	 * Apply the made changes to the form. For stored forms, this will
	 * perform the Updater process
	 * @return $this
	 */
	public function apply() : Creator {
		$this->FormObject->updateDefinition($this->a_definition, $this->Component->_getFieldRenames());
		
		$this->Component->_resetFieldRenames();
		
		return $this;
	}
	
}
