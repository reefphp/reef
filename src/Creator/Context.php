<?php

namespace Reef\Creator;

/**
 * The Creator Context provides functionality shared by all the contexts
 */
abstract class Context {
	
	/**
	 * The Creator object
	 * @type \Reef\Creator\Creator
	 */
	protected $Creator;
	
	/**
	 * The Reef object
	 * @type \Reef\Reef
	 */
	protected $Reef;
	
	/**
	 * The form definition the creator works on
	 * @type array
	 */
	protected $a_definition;
	
	/**
	 * Constructor
	 * @param \Reef\Creator\Creator $Creator The Creator object
	 * @param array &$a_definition The form definition the creator works on
	 */
	public function __construct(Creator $Creator, array &$a_definition) {
		$this->Creator = $Creator;
		$this->Reef = $Creator->getFormObject()->getReef();
		$this->a_definition = &$a_definition;
	}
	
	/**
	 * Retrieve the Form creator context
	 * @return \Reef\Creator\Form
	 */
	abstract public function getForm() : Form;
	
	/**
	 * @see \Reef\Creator\Component::addField()
	 */
	public function addField(string $s_componentName) : Component {
		return $this->Creator->_getComponent()->addField($s_componentName);
	}
	
	/**
	 * @see \Reef\Creator\Component::getFieldByName()
	 */
	public function getFieldByName(string $s_fieldName) : Component {
		return $this->Creator->_getComponent()->getFieldByName($s_fieldName);
	}
	
	/**
	 * @see \Reef\Creator\Component::getFieldByIndex()
	 */
	public function getFieldByIndex(int $i_index) : Component {
		return $this->Creator->_getComponent()->getFieldByIndex($i_index);
	}
	
	/**
	 * @see \Reef\Creator\Component::getFieldByPosition()
	 */
	public function getFieldByPosition(int $i_position) : Component {
		return $this->Creator->_getComponent()->getFieldByPosition($i_position);
	}
	
	/**
	 * @see \Reef\Creator\Component::getFirstField()
	 */
	public function getFirstField() : Component {
		return $this->Creator->_getComponent()->getFirstField();
	}
	
	/**
	 * @see \Reef\Creator\Component::getLastField()
	 */
	public function getLastField() : Component {
		return $this->Creator->_getComponent()->getLastField();
	}
	
	/**
	 * @see \Reef\Creator\Creator::apply()
	 */
	public function apply() : Creator {
		return $this->Creator->apply();
	}
	
}
