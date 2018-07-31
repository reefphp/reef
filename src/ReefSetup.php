<?php

namespace Reef;

use Reef\Components\Component;
use \Reef\Storage\StorageFactory;
use \Reef\Layout\Layout;

class ReefSetup {
	
	private $StorageFactory;
	private $Layout;
	
	private $a_componentMapping = [];
	
	private $Reef;
	
	public function __construct(StorageFactory $StorageFactory, Layout $Layout) {
		$this->StorageFactory = $StorageFactory;
		$this->Layout = $Layout;
		
		$this->addComponent(new \Reef\Components\SingleLineText\SingleLineTextComponent);
		$this->addComponent(new \Reef\Components\Textarea\TextareaComponent);
		$this->addComponent(new \Reef\Components\SingleCheckbox\SingleCheckboxComponent);
		$this->addComponent(new \Reef\Components\TextNumber\TextNumberComponent);
		$this->addComponent(new \Reef\Components\Heading\HeadingComponent);
		$this->addComponent(new \Reef\Components\Hidden\HiddenComponent);
		$this->addComponent(new \Reef\Components\OptionList\OptionListComponent);
		$this->addComponent(new \Reef\Components\Select\SelectComponent);
	}
	
	public function getStorageFactory() : StorageFactory {
		return $this->StorageFactory;
	}
	
	public function getLayout() : Layout {
		return $this->Layout;
	}
	
	public function checkSetup(\Reef\Reef $Reef) {
		$this->Reef = $Reef;
		
		foreach($this->a_componentMapping as $Component) {
			$Component->setReef($this->Reef);
		}
		
		$s_layout = $this->Layout->getName();
		
		foreach($this->a_componentMapping as $Component) {
			foreach($Component->requiredComponents() as $s_requiredComponent) {
				if(!isset($this->a_componentMapping[$s_requiredComponent])) {
					throw new \Exception("Component ".$Component::COMPONENT_NAME." requires component ".$s_requiredComponent.".");
				}
			}
			
			if(!in_array($s_layout, $Component->supportedLayouts())) {
				throw new \Exception("Component ".$Component::COMPONENT_NAME." does not support layout ".$s_layout.".");
			}
		}
	}
	
	public function getComponentMapping() {
		return $this->a_componentMapping;
	}
	
	public function getDefaultBuilderComponents() {
		$a_components = [
			'reef:heading',
			'reef:single_line_text',
			'reef:textarea',
			'reef:text_number',
			'reef:single_checkbox',
			'reef:select',
		];
		
		return array_intersect($a_components, array_keys($this->a_componentMapping));
	}
	
	public function addComponent(Component $Component) {
		if(!empty($this->Reef)) {
			throw new \Exception("Can only add components during Reef setup.");
		}
		$this->a_componentMapping[$Component::COMPONENT_NAME] = $Component;
	}
	
	public function hasComponent($s_componentName) {
		return isset($this->a_componentMapping[$s_componentName]);
	}
	
	public function getComponent($s_componentName) {
		return $this->a_componentMapping[$s_componentName];
	}
	
	public function getField(array $a_declaration, Form $Form) {
		if(!isset($this->a_componentMapping[$a_declaration['component']])) {
			throw new \DomainException("Component not loaded: ".$a_declaration['component']);
		}
		
		return $this->a_componentMapping[$a_declaration['component']]->newField($a_declaration, $Form);
	}
	
	
}
