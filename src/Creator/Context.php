<?php

namespace Reef\Creator;

abstract class Context {
	
	protected $Creator;
	protected $Reef;
	protected $a_definition;
	
	public function __construct(Creator $Creator, array &$a_definition) {
		$this->Creator = $Creator;
		$this->Reef = $Creator->getFormObject()->getReef();
		$this->a_definition = &$a_definition;
	}
	
	abstract public function getForm() : Form;
	
	public function addField(string $s_componentName) : Component {
		return $this->Creator->_getComponent()->addField($s_componentName);
	}
	
	public function getFieldByName(string $s_fieldName) : Component {
		return $this->Creator->_getComponent()->getFieldByName($s_fieldName);
	}
	
	public function getFieldByIndex(int $i_index) : Component {
		return $this->Creator->_getComponent()->getFieldByIndex($i_index);
	}
	
	public function getFieldByPosition(int $i_position) : Component {
		return $this->Creator->_getComponent()->getFieldByPosition($i_position);
	}
	
	public function getFirstField() : Component {
		return $this->Creator->_getComponent()->getFirstField();
	}
	
	public function getLastField() : Component {
		return $this->Creator->_getComponent()->getLastField();
	}
	
	public function apply() : Creator {
		return $this->Creator->apply();
	}
	
}
