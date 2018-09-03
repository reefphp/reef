<?php

namespace Reef;

use \Reef\Form\Form;
use \Reef\Components\Component;
use \Reef\Storage\StorageFactory;
use \Reef\Storage\NoStorageFactory;
use \Reef\Layout\Layout;
use \Reef\Session\SessionInterface;
use \Reef\Exception\LogicException;
use \Reef\Exception\DomainException;
use \Reef\Exception\BadMethodCallException;

require_once(__DIR__ . '/functions.php');

class ReefSetup {
	
	private $StorageFactory;
	private $a_layouts;
	private $SessionObject;
	
	private $a_componentMapping = [];
	private $s_currentLayout;
	
	private $Reef;
	
	public function __construct(StorageFactory $StorageFactory, $a_layouts, SessionInterface $SessionObject) {
		$this->StorageFactory = $StorageFactory;
		$this->SessionObject = $SessionObject;
		
		if(!is_array($a_layouts)) {
			$a_layouts = [$a_layouts];
		}
		$this->a_layouts = [];
		foreach($a_layouts as $Layout) {
			if(!($Layout instanceof Layout)) {
				throw new LogicException("Caught invalid layout object");
			}
			$this->a_layouts[$Layout::getName()] = $Layout;
		}
		$this->s_currentLayout = \Reef\array_first_key($this->a_layouts);
		
		$this->addComponent(new \Reef\Components\TextLine\TextLineComponent);
		$this->addComponent(new \Reef\Components\Textarea\TextareaComponent);
		$this->addComponent(new \Reef\Components\Checkbox\CheckboxComponent);
		$this->addComponent(new \Reef\Components\Radio\RadioComponent);
		$this->addComponent(new \Reef\Components\CheckList\CheckListComponent);
		$this->addComponent(new \Reef\Components\TextNumber\TextNumberComponent);
		$this->addComponent(new \Reef\Components\Heading\HeadingComponent);
		$this->addComponent(new \Reef\Components\Paragraph\ParagraphComponent);
		$this->addComponent(new \Reef\Components\Hidden\HiddenComponent);
		$this->addComponent(new \Reef\Components\OptionList\OptionListComponent);
		$this->addComponent(new \Reef\Components\Select\SelectComponent);
		$this->addComponent(new \Reef\Components\Condition\ConditionComponent);
		$this->addComponent(new \Reef\Components\Submit\SubmitComponent);
	}
	
	public function getStorageFactory() : StorageFactory {
		return $this->StorageFactory;
	}
	
	public function getLayout() : Layout {
		return $this->a_layouts[$this->s_currentLayout];
	}
	
	public function setLayout(?string $s_layoutName) {
		if($s_layoutName == null) {
			$this->s_currentLayout = \Reef\array_first_key($this->a_layouts);
			return;
		}
		
		if(!isset($this->a_layouts[$s_layoutName])) {
			throw new DomainException("Unknown layout ".$s_layoutName);
		}
		
		$this->s_currentLayout = $s_layoutName;
	}
	
	public function getSessionObject() : SessionInterface {
		return $this->SessionObject;
	}
	
	public function checkSetup(\Reef\Reef $Reef) {
		$this->Reef = $Reef;
		
		foreach($this->a_componentMapping as $Component) {
			$Component->setReef($this->Reef);
			$Component->checkSetup();
		}
		
		$s_storage = $this->StorageFactory::getName();
		
		foreach($this->a_componentMapping as $Component) {
			foreach($Component->requiredComponents() as $s_requiredComponent) {
				if(!isset($this->a_componentMapping[$s_requiredComponent])) {
					throw new LogicException("Component ".$Component::COMPONENT_NAME." requires component ".$s_requiredComponent.".");
				}
			}
			
			foreach($this->a_layouts as $s_layout => $Layout) {
				if(!in_array($s_layout, $Component->supportedLayouts())) {
					throw new LogicException("Component ".$Component::COMPONENT_NAME." does not support layout ".$s_layout.".");
				}
			}
			
			$a_supportedStorages = $Component->supportedStorages();
			if($a_supportedStorages !== null && $s_storage != NoStorageFactory::getName() && !in_array($s_storage, $a_supportedStorages)) {
				throw new LogicException("Component ".$Component::COMPONENT_NAME." does not support storage ".$s_storage.".");
			}
		}
	}
	
	public function getComponentMapping() {
		return $this->a_componentMapping;
	}
	
	public function getDefaultBuilderComponents() {
		$a_components = [
			'reef:heading',
			'reef:paragraph',
			'reef:text_line',
			'reef:textarea',
			'reef:text_number',
			'reef:checkbox',
			'reef:checklist',
			'reef:radio',
			'reef:select',
			'reef:upload',
		];
		
		return array_intersect($a_components, array_keys($this->a_componentMapping));
	}
	
	public function addComponent(Component $Component) {
		if(!empty($this->Reef)) {
			throw new BadMethodCallException("Can only add components during Reef setup.");
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
			throw new DomainException("Component not loaded: ".$a_declaration['component']);
		}
		
		return $this->a_componentMapping[$a_declaration['component']]->newField($a_declaration, $Form);
	}
	
	
}
