<?php

namespace Reef;

use Reef\Components\Component;

class ComponentMapper {
	
	private $Reef;
	
	private $a_mapping = [];
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	public function getMapping() {
		return $this->a_mapping;
	}
	
	public function add(Component $Component) {
		$Component->setReef($this->Reef);
		$this->a_mapping[$Component::COMPONENT_NAME] = $Component;
	}
	
	public function getComponent($s_componentName) {
		return $this->a_mapping[$s_componentName];
	}
	
	public function getField(array $a_config, Form $Form) {
		if(!isset($this->a_mapping[$a_config['component']])) {
			throw new \DomainException("Component not loaded: ".$a_config['component']);
		}
		
		return $this->a_mapping[$a_config['component']]->newField($a_config, $Form);
	}
	
	
}
