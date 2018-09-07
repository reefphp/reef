<?php

namespace Reef;

use \Reef\Form\Form;
use \Reef\Extension\Extension;
use \Reef\Components\Component;
use \Reef\Storage\StorageFactory;
use \Reef\Storage\NoStorageFactory;
use \Reef\Layout\Layout;
use \Reef\Session\SessionInterface;
use \Reef\Exception\LogicException;
use \Reef\Exception\DomainException;
use \Reef\Exception\BadMethodCallException;

require_once(__DIR__ . '/functions.php');

/**
 * The Reef Setup defines the Reef environment before initializing Reef itself. This way, it is possible
 * to validate the correctness of the environment such that any compatibility issues are noticed before
 * anything crucial is done.
 */
class ReefSetup {
	
	/**
	 * The storage factory of the Reef
	 * @type StorageFactory
	 */
	private $StorageFactory;
	
	/**
	 * All layouts to use
	 * @type Layout[]
	 */
	private $a_layouts;
	
	/**
	 * The session object of the Reef
	 * @type SessionInterface
	 */
	private $SessionObject;
	
	/**
	 * The component mapping, mapping component names to component objects
	 * @type Component[]
	 */
	private $a_componentMapping = [];
	
	/**
	 * The extension mapping, mapping extension names to extension objects
	 * @type Extension[]
	 */
	private $a_extensions = [];
	
	/**
	 * The currently active layout
	 * @type string
	 */
	private $s_currentLayout;
	
	/**
	 * The Reef of this setup (only available after checkSetup() has been called)
	 * @type \Reef\Reef
	 */
	private $Reef;
	
	/**
	 * Initialization
	 * @param StorageFactory $StorageFactory The storage factory to use. Use a NoStorageFactory object for no storage
	 * @param Layout|Layout[] $a_layouts A layout or array of layouts to use
	 * @param SessionInterface $SessionObject The session object to use. Use a NoSession object for no sessions
	 */
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
	
	/**
	 * Get the storage factory
	 * @return StorageFactory
	 */
	public function getStorageFactory() : StorageFactory {
		return $this->StorageFactory;
	}
	
	/**
	 * Get the current layout
	 * @return Layout
	 */
	public function getLayout() : Layout {
		return $this->a_layouts[$this->s_currentLayout];
	}
	
	/**
	 * Switch the current layout
	 * @param ?string $s_layoutName The layout name, or null for the default layout
	 */
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
	
	/**
	 * Get the session object
	 * @return SessionInterface
	 */
	public function getSessionObject() : SessionInterface {
		return $this->SessionObject;
	}
	
	/**
	 * Check the integrity of this setup
	 * @param \Reef\Reef $Reef The newly created Reef object
	 * @throws LogicException If the configuration is not valid
	 */
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
		
		foreach($this->a_extensions as $Extension) {
			$Extension->checkSetup();
		}
		
		foreach($this->a_extensions as $Extension) {
			foreach($this->a_layouts as $s_layout => $Layout) {
				if(!in_array($s_layout, $Extension->supportedLayouts())) {
					throw new LogicException("Extension ".$Extension::getName()." does not support layout ".$s_layout.".");
				}
			}
		}
	}
	
	/**
	 * Get the component mapping
	 * @return Component[]
	 */
	public function getComponentMapping() {
		return $this->a_componentMapping;
	}
	
	/**
	 * Get a default list of components to use in the builder
	 * @return string[] The component names
	 */
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
	
	/**
	 * Add a component. Should be called before initializing Reef
	 * @param Component $Component The new component to add to the component mapping
	 * @throws BadMethodCallException If Reef is already initialized
	 */
	public function addComponent(Component $Component) {
		if(!empty($this->Reef)) {
			throw new BadMethodCallException("Can only add components during Reef setup.");
		}
		$this->a_componentMapping[$Component::COMPONENT_NAME] = $Component;
	}
	
	/**
	 * Determine whether this setup contains the specified component name
	 * @param string $s_componentName The component to check
	 * @return bool
	 */
	public function hasComponent($s_componentName) {
		return isset($this->a_componentMapping[$s_componentName]);
	}
	
	/**
	 * Retrieve the component with the specified component name
	 * @param string $s_componentName The component to check
	 * @return Component
	 * @throws DomainException If the component does not exist
	 */
	public function getComponent($s_componentName) {
		if(!isset($this->a_componentMapping[$s_componentName])) {
			throw new DomainException("Component not loaded: ".$s_componentName);
		}
		
		return $this->a_componentMapping[$s_componentName];
	}
	
	/**
	 * Create a new field from the given declaration
	 * @param array $a_declaration The field declaration
	 * @param Form $Form The form the field belongs to
	 * @return Field
	 * @throws DomainException If the component does not exist
	 */
	public function getField(array $a_declaration, Form $Form) {
		if(!isset($this->a_componentMapping[$a_declaration['component']])) {
			throw new DomainException("Component not loaded: ".$a_declaration['component']);
		}
		
		return $this->a_componentMapping[$a_declaration['component']]->newField($a_declaration, $Form);
	}
	
	/**
	 * Add an extension. Should be called before initializing Reef
	 * @param Extension $Extension The new extension to add
	 * @throws BadMethodCallException If Reef is already initialized
	 */
	public function addExtension(Extension $Extension) {
		if(!empty($this->Reef)) {
			throw new BadMethodCallException("Can only add extensions during Reef setup.");
		}
		$this->a_extensions[$Extension::getName()] = $Extension;
	}
	
	/**
	 * Return the list of added extensions
	 * @return Extension[]
	 */
	public function getExtensionMapping() {
		return $this->a_extensions;
	}
	
	
}
