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
	
	public function add($s_componentClass) {
		if(!class_exists($s_componentClass) || !is_subclass_of($s_componentClass, 'Reef\\Components\\Component')) {
			throw new \InvalidArgumentException("Provided class is not a component: ".$s_componentClass);
		}
		
		$this->a_mapping[$s_componentClass::COMPONENT_NAME] = $s_componentClass;
	}
	
	public function get(array $a_config, Form $Form) {
		if(!isset($this->a_mapping[$a_config['component']])) {
			throw new \DomainException("Component not loaded: ".$a_config['component']);
		}
		
		return new $this->a_mapping[$a_config['component']]($a_config, $Form);
	}
	
	
}
