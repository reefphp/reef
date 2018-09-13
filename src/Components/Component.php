<?php

namespace Reef\Components;

use Reef\Form\Form;
use Reef\Reef;
use Reef\Locale\Trait_ComponentLocale;
use \Reef\Assets\JSCSSAssetsTrait;
use Symfony\Component\Yaml\Yaml;
use \Reef\Components\Traits\Hidable\HidableComponentInterface;
use \Reef\Components\Traits\Hidable\HidableComponentTrait;

/**
 * A component describes the appearance and behaviour of a specific element that can be used
 * in a form. It can be e.g. a text input, checkbox, title or paragraph of text. A component
 * added to a Form is called a field. Whenever a field is filled in, its value is represented
 * by a FieldValue object.
 * A Form is a set of Fields; all FieldValue objects of all Fields in a Form are agglomorated
 * into a single Submission object.
 */
abstract class Component implements HidableComponentInterface {
	
	use Trait_ComponentLocale;
	use HidableComponentTrait;
	use JSCSSAssetsTrait;
	
	/**
	 * The Reef object this component instance if member of
	 * @type Reef
	 */
	protected $Reef;
	
	/**
	 * The component configuration
	 * @type array
	 */
	protected $a_configuration;
	
	/**
	 * Cached result of getConditionOperators()
	 * @type array
	 */
	private $a_conditionOperators = null;
	
	/**
	 * Cached result of getBuilderOperators()
	 * @type array
	 */
	private $a_builderOperators = null;
	
	/**
	 * Cached result of getTemplateLoader()
	 * @type FilesystemLoader[]
	 */
	private $a_filesystemLoaders = [];
	
	/**
	 * Custom layout directories
	 * @type string[]
	 */
	private $a_customLayoutDirs = [];
	
	/**
	 * Cached result of supportedLayouts() (defaults to -1 for 'not initialized')
	 * @type string[]
	 */
	private $a_supportedLayouts = -1;
	
	/**
	 * Set the Reef object
	 * @param Reef $Reef The Reef we are currently working in
	 */
	public function setReef(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	/**
	 * Get the Reef object
	 * @return Reef The Reef we are currently working in
	 */
	public function getReef() : Reef {
		return $this->Reef;
	}
	
	/**
	 * Initialize the component
	 * @param \Reef\ReefSetup $ReefSetup The setup
	 */
	public function init($ReefSetup) {
	}
	
	/**
	 * Check the setup, may throw an exception if something is not valid
	 */
	public function checkSetup() {
	}
	
	/**
	 * Get the parent component
	 * @return Component The parent component
	 */
	public function getParent() {
		return (static::PARENT_NAME == null) ? null : $this->Reef->getSetup()->getComponent(static::PARENT_NAME);
	}
	
	/**
	 * Define the assets for this component
	 * @return array The assets, where key is the asset name and value is the path to the asset
	 */
	public function getAssets() : array {
		return $this->getConfiguration()['assets']??[];
	}
	
	/**
	 * Perform validation on a declaration, additional to the default form validation
	 * @param array $a_declaration The declaration array for the field
	 * @param array &$a_errors Array of errors
	 * @return bool True if valid
	 */
	public function validateDeclaration(array $a_declaration, array &$a_errors = null) : bool {
		return true;
	}
	
	/**
	 * Return a new field from this component
	 * @param array $a_declaration The declaration array for the field
	 * @param Form $Form The form the field belongs to
	 * @return Field
	 */
	public function newField(array $a_declaration, Form $Form) : Field {
		$s_class = substr(static::class, 0, -9).'Field';
		return new $s_class($a_declaration, $Form, $this);
	}
	
	/**
	 * Return the base directory of the current component
	 * @return string The directory
	 */
	abstract public static function getDir() : string;
	
	/**
	 * Returns an array of required components
	 * @return array
	 */
	public function requiredComponents() : array {
		$a_components = [];
		
		if(static::PARENT_NAME !== null) {
			$a_components = array_flip($this->getParent()->requiredComponents());
			$a_components[static::PARENT_NAME] = 1;
		}
		
		$a_configuration = $this->getConfiguration();
		foreach($a_configuration['basicDefinition']['fields']??[] as $a_field) {
			$a_components[$a_field['component']] = 1;
		}
		foreach($a_configuration['advancedDefinition']['fields']??[] as $a_field) {
			$a_components[$a_field['component']] = 1;
		}
		
		unset($a_components[static::COMPONENT_NAME]);
		
		return array_keys($a_components);
	}
	
	/**
	 * Returns an array of supported layouts. May return null to indicate that
	 * this component is layout-agnostic
	 * @return ?array
	 */
	public function supportedLayouts() : ?array {
		if($this->a_supportedLayouts !== -1) {
			return $this->a_supportedLayouts;
		}
		
		// Get own supported layouts
		$a_supportedLayouts = $this->Reef->cache('supportedLayouts.component.'.static::COMPONENT_NAME, function() {
			$s_viewDir = static::getDir().'/view/';
			$i_viewDirLen = strlen($s_viewDir);
			$a_layouts = glob($s_viewDir . '*', GLOB_ONLYDIR);
			
			$a_layouts = array_map(function($s_layout) use($i_viewDirLen) {
				return substr($s_layout, $i_viewDirLen);
			}, $a_layouts);
			
			if(in_array('default', $a_layouts)) {
				return null;
			}
			return $a_layouts;
		});
		
		if($a_supportedLayouts === null) {
			$this->a_supportedLayouts = null;
			return null;
		}
		
		// Merge custom supported layouts
		$a_supportedLayouts = array_merge($a_supportedLayouts, array_keys($this->a_customLayoutDirs));
		
		// Merge parent supported layouts
		if(static::PARENT_NAME !== null) {
			$a_parentSupportedLayouts = $this->getParent()->supportedLayouts();
			if($a_parentSupportedLayouts === null) {
				$this->a_supportedLayouts = null;
				return null;
			}
			
			$a_supportedLayouts = array_merge($a_supportedLayouts, $a_parentSupportedLayouts);
		}
		
		$a_supportedLayouts = array_unique($a_supportedLayouts);
		
		// Cache the result if not still initializing
		if($this->Reef !== null) {
			$this->a_supportedLayouts = $a_supportedLayouts;
		}
		
		return $a_supportedLayouts;
	}
	
	/**
	 * Add a layout for this component
	 * @param string $s_layout The layout
	 * @param string $s_templateDir The template dir
	 * @param string $s_subDir Optionally, the subdirectory within the template dir to use
	 */
	public function addLayout($s_layout, $s_templateDir, $s_subDir = null) {
		if($this->Reef !== null) {
			throw new \Reef\Exception\LogicException("Can only add layouts during initialization");
		}
		
		$this->a_customLayoutDirs[$s_layout] = [
			'template_dir' => $s_templateDir,
			'default_sub_dir' => $s_subDir,
		];
	}
	
	/**
	 * Returns an array of supported storages. May return null to indicate that
	 * no storage is required for this component, which implies there is no storage dependency.
	 * @return ?array
	 */
	abstract public function supportedStorages() : ?array;
	
	/**
	 * Return a list of class names, of this class and all its parents, except this abstract Component class
	 * @return string[] The class names
	 */
	public function getInheritanceList() {
		$s_currentClass = get_called_class();
		$a_classes = array_merge([$s_currentClass], class_parents($s_currentClass));
		array_pop($a_classes); // Remove the Component class
		return array_values($a_classes);
	}
	
	/**
	 * Returns the template filesystem loader for this component
	 * @param ?string $s_layout The layout to use. Optional, defaults to the layout set in the Reef setup
	 * @param string $s_file The template name
	 * @return FilesystemLoader The loader
	 */
	public function getTemplateLoader($s_layout = null, string $s_file = 'form') {
		if(empty($s_layout)) {
			$s_layout = $this->Reef->getSetup()->getLayout()->getName();
		}
		
		if(!empty($this->a_filesystemLoaders[$s_layout][$s_file])) {
			return $this->a_filesystemLoaders[$s_layout][$s_file];
		}
		
		// Resolve custom layouts
		$Component = $this;
		do {
			if(isset($Component->a_customLayoutDirs[$s_layout])) {
				$a_customLayout = $Component->a_customLayoutDirs[$s_layout];
				if(file_exists($a_customLayout['template_dir'] . '/' . ($a_customLayout['default_sub_dir']??'') . '/' . $s_file.'.mustache')) {
					$this->a_filesystemLoaders[$s_layout][$s_file] = new \Reef\Mustache\FilesystemLoader($this->Reef, $a_customLayout['template_dir'], ['default_sub_dir' => $a_customLayout['default_sub_dir']]);
					return $this->a_filesystemLoaders[$s_layout][$s_file];
				}
			}
		} while(null !== ($Component = $Component->getParent()));
		
		// Resolve own layouts
		$s_templateDir = null;
		$s_viewfile = 'view/'.$s_layout.'/'.$s_file.'.mustache';
		
		$a_classes = $this->getInheritanceList();
		foreach($a_classes as $s_class) {
			if(file_exists($s_class::getDir() . $s_viewfile)) {
				$s_templateDir = $s_class::getDir();
				break;
			}
		}
		
		if($s_templateDir === null) {
			if($s_layout == 'default') {
				// @codeCoverageIgnoreStart
				throw new \Reef\Exception\ResourceNotFoundException("Could not find ".$s_file." template file for component '".static::COMPONENT_NAME."'.");
				// @codeCoverageIgnoreEnd
			}
			else {
				$this->a_filesystemLoaders[$s_layout][$s_file] = $this->getTemplateLoader('default', $s_file);
			}
		}
		else {
			$this->a_filesystemLoaders[$s_layout][$s_file] = new \Reef\Mustache\FilesystemLoader($this->Reef, $s_templateDir, ['default_sub_dir' => 'view/'.$s_layout]);
		}
		
		return $this->a_filesystemLoaders[$s_layout][$s_file];
	}
	
	/**
	 * Returns the raw HTML template of this component
	 * @param ?string $s_layout The layout to use. Optional, defaults to the layout set in the Reef setup
	 * @return string The template
	 */
	public function getTemplate($s_layout = null) {
		return $this->getTemplateLoader($s_layout)->load('form');
	}
	
	/**
	 * Return the configuration array of this component
	 * @return array
	 */
	public function getConfiguration() : array {
		if($this->a_configuration !== null) {
			return $this->a_configuration;
		}
		
		$s_extensionKey = $this->Reef->getExtensionCollection()->getCollectionHash();
		
		$this->a_configuration = $this->Reef->cache('configuration.component.'.static::COMPONENT_NAME.'.'.$s_extensionKey, function() {
			$a_configuration = Yaml::parseFile(static::getDir().'config.yml');
			
			$a_configuration['basicDefinition']['fields'] = $a_configuration['basicDefinition']['fields']??[];
			$a_configuration['advancedDefinition']['fields'] = $a_configuration['advancedDefinition']['fields']??[];
			
			// Merge generalized options
			$a_hidableFields = $this->getDeclarationFields_hidable();
			if(!empty($a_hidableFields)) {
				array_push($a_configuration['basicDefinition']['fields'], ...$a_hidableFields);
			}
			
			if($this instanceof \Reef\Components\Traits\Required\RequiredComponentInterface) {
				$a_requiredFields = $this->getDeclarationFields_required();
				if(!empty($a_requiredFields)) {
					array_push($a_configuration['basicDefinition']['fields'], ...$a_requiredFields);
				}
				
				$a_configuration['advancedLocale'] = array_merge($a_configuration['advancedLocale']??[], $this->getLocale_required());
			}
			
			// Merge event options
			$a_eventDefs = [
				'basicDefinition' => [],
				'advancedDefinition' => [],
				'basicLocale' => [],
				'advancedLocale' => [],
			];
			/**
			 * Event allowing to add fields to form definitions of component configurations
			 * @event reef.component_configuration
			 * @var Component component The component
			 * @var array basic_definition The basic definition
			 * @var array advanced_definition The advanced definition
			 * @var array basic_locale The basic locale
			 * @var array advanced_locale The advanced locale
			 */
			$this->Reef->getExtensionCollection()->event('reef.component_configuration', [
				'component' => $this,
				'basic_definition' => &$a_eventDefs['basicDefinition'],
				'advanced_definition' => &$a_eventDefs['advancedDefinition'],
				'basic_locale' => &$a_eventDefs['basicLocale'],
				'advanced_locale' => &$a_eventDefs['advancedLocale'],
			]);
			foreach(['basicDefinition', 'advancedDefinition'] as $s_type) {
				if(!empty($a_eventDefs[$s_type])) {
					array_push($a_configuration[$s_type]['fields'], ...$a_eventDefs[$s_type]);
				}
			}
			$a_configuration['basicLocale'] = array_merge($a_configuration['basicLocale']??[], $a_eventDefs['basicLocale']??[]);
			$a_configuration['advancedLocale'] = array_merge($a_configuration['advancedLocale']??[], $a_eventDefs['advancedLocale']??[]);
			
			// Parse locale declaration lists
			foreach(['basicLocale', 'advancedLocale'] as $s_localeType) {
				if(!isset($a_configuration[$s_localeType])) {
					continue;
				}
				
				foreach($a_configuration[$s_localeType] as $s_langKey => $m_langConfig) {
					if(is_string($m_langConfig)) {
						$a_configuration[$s_localeType][$s_langKey] = [
							'title_key' => $m_langConfig,
						];
					}
				}
			}
			
			// Merge locale
			$a_configuration['locale'] = array_merge($a_configuration['basicLocale']??[], $a_configuration['advancedLocale']??[]);
			$a_configuration['internalLocale'] = $a_configuration['internalLocale']??[];
			
			// Merge parent
			$ParentComponent = $this->getParent();
			if(empty($ParentComponent)) {
				return $a_configuration;
			}
			
			$a_parentConfiguration = $ParentComponent->getConfiguration();
			
			$a_configuration['basicLocale'] = array_merge($a_parentConfiguration['basicLocale'], $a_configuration['basicLocale']??[]);
			$a_configuration['advancedLocale'] = array_merge($a_parentConfiguration['advancedLocale'], $a_configuration['advancedLocale']??[]);
			$a_configuration['locale'] = array_merge($a_configuration['basicLocale'], $a_configuration['advancedLocale']);
			$a_configuration['internalLocale'] = array_merge($a_configuration['internalLocale']??[], $a_configuration['internalLocale']??[]);
			
			[
				$a_configuration['basicDefinition']['fields'],
				$a_configuration['advancedDefinition']['fields'],
				$a_configuration['props']
			] = $this->mergeConfigurationFieldsProps(
				$a_configuration['basicDefinition']['fields']??[],
				$a_configuration['advancedDefinition']['fields']??[],
				$a_configuration['props']??[],
				$a_parentConfiguration['basicDefinition']['fields']??[],
				$a_parentConfiguration['advancedDefinition']['fields']??[],
				$a_parentConfiguration['props']??[]
			);
			
			return $a_configuration;
		});
		
		return $this->a_configuration;
	}
	
	/**
	 * Return all possible condition operators of this component
	 * By default, this returns an empty array to indicate conditions are not supported
	 * 
	 * @return string[]
	 */
	public function getConditionOperators() : array {
		if($this->a_conditionOperators !== null) {
			return $this->a_conditionOperators;
		}
		
		$this->a_conditionOperators = $this->getConfiguration()['builder_operators']??[];
		
		return $this->a_conditionOperators;
	}
	
	/**
	 * Return all possible condition operators of this component that should be provided in the builder
	 * By default, this returns an empty array to indicate conditions are not supported in the builder
	 * 
	 * @return string[]
	 */
	public function getBuilderOperators() : array {
		if($this->a_builderOperators !== null) {
			return $this->a_builderOperators;
		}
		
		$a_locale = $this->getLocale();
		
		$this->a_builderOperators = [];
		
		foreach($this->getConditionOperators() as $s_opKey => $s_operator) {
			$this->a_builderOperators[$s_operator] = $a_locale['operator_'.$s_opKey]??'';
		}
		
		return $this->a_builderOperators;
	}
	
	/**
	 * Merge configuration fields & properties of this component with its parents fields & properties
	 * @param array $a_basicFields
	 * @param array $a_advancedFields
	 * @param array $a_props
	 * @param array $a_parentBasicFields
	 * @param array $a_parentAdvancedFields
	 * @param array $a_parentProps
	 * @return [array, array, array] merged fields & props
	 */
	private function mergeConfigurationFieldsProps($a_basicFields, $a_advancedFields, $a_props, $a_parentBasicFields, $a_parentAdvancedFields, $a_parentProps) {
		
		// Create mapping from name to field index
		$a_parentBasicFieldsKeys = [];
		foreach($a_parentBasicFields as $i_index => $a_field) {
			if(isset($a_field['name'])) {
				$a_parentBasicFieldsKeys[$a_field['name']] = $i_index;
			}
		}
		
		$a_parentAdvancedFieldsKeys = [];
		foreach($a_parentAdvancedFields as $i_index => $a_field) {
			if(isset($a_field['name'])) {
				$a_parentAdvancedFieldsKeys[$a_field['name']] = $i_index;
			}
		}
		
		$a_parentPropsKeys = array_flip(array_column($a_parentProps, 'name'));
		
		// We take the parent fields & props as base
		$a_returnBasicFields = $a_parentBasicFields;
		$a_returnAdvancedFields = $a_parentAdvancedFields;
		$a_returnProps = $a_parentProps;
		
		$fn_rmBasic = function($s_name) use(&$a_returnBasicFields, $a_parentBasicFieldsKeys)  {
			if(isset($a_parentBasicFieldsKeys[$s_name])) {
				unset($a_returnBasicFields[$a_parentBasicFieldsKeys[$s_name]]);
			}
		};
		
		$fn_rmAdv = function($s_name) use(&$a_returnAdvancedFields, $a_parentAdvancedFieldsKeys)  {
			if(isset($a_parentAdvancedFieldsKeys[$s_name])) {
				unset($a_returnAdvancedFields[$a_parentAdvancedFieldsKeys[$s_name]]);
			}
		};
		
		$fn_rmProp = function($s_name) use(&$a_returnProps, $a_parentPropsKeys)  {
			if(isset($a_parentPropsKeys[$s_name])) {
				unset($a_returnProps[$a_parentPropsKeys[$s_name]]);
			}
		};
		
		// Merge new basic fields
		foreach($a_basicFields as $a_field) {
			if(!isset($a_field['name'])) {
				$a_returnBasicFields[] = $a_field;
				continue;
			}
			
			$fn_rmProp($a_field['name']);
			$fn_rmAdv($a_field['name']);
			
			if(isset($a_parentBasicFieldsKeys[$a_field['name']])) {
				$a_returnBasicFields[$a_parentBasicFieldsKeys[$a_field['name']]] = $a_field;
			}
			else {
				$a_returnBasicFields[] = $a_field;
			}
		}
		
		// Merge new advanced fields
		foreach($a_advancedFields as $a_field) {
			if(!isset($a_field['name'])) {
				$a_returnAdvancedFields[] = $a_field;
				continue;
			}
			
			$fn_rmProp($a_field['name']);
			$fn_rmBasic($a_field['name']);
			
			if(isset($a_parentAdvancedFieldsKeys[$a_field['name']])) {
				$a_returnAdvancedFields[$a_parentAdvancedFieldsKeys[$a_field['name']]] = $a_field;
			}
			else {
				$a_returnAdvancedFields[] = $a_field;
			}
		}
		
		// Merge new props
		foreach($a_props as $a_prop) {
			
			$fn_rmBasic($a_field['name']);
			$fn_rmAdv($a_field['name']);
			
			if(isset($a_parentPropsKeys[$a_prop['name']])) {
				$a_returnProps[$a_parentPropsKeys[$a_prop['name']]] = $a_prop;
			}
			else {
				$a_returnProps[] = $a_prop;
			}
		}
		
		// Use array_values() to make sure we don't leave gaps in the keys
		return [array_values($a_returnBasicFields), array_values($a_returnAdvancedFields), array_values($a_returnProps)];
	}
	
	/**
	 * Get names of this component's props
	 * @return string[]
	 */
	public function getPropNames() {
		$a_props = $this->getConfiguration()['props']??[];
		
		return array_column($a_props, 'name');
	}
	
	/**
	 * Generate the basic declaration form
	 * @return TempForm
	 */
	public function generateBasicDeclarationForm() {
		return $this->generateDeclarationForm('basic');
	}
	
	/**
	 * Generate the advanced declaration form
	 * @return TempForm
	 */
	public function generateAdvancedDeclarationForm() {
		return $this->generateDeclarationForm('advanced');
	}
	
	/**
	 * Generate the combined basic and advanced declaration form
	 * @return TempForm
	 */
	public function generateCombinedDeclarationForm() {
		return $this->generateDeclarationForm('combined');
	}
	
	/**
	 * Generate a declaration form
	 * @param string $s_type The type, either 'basic', 'advanced' or 'combined'
	 * @return TempForm
	 */
	private function generateDeclarationForm(string $s_type) {
		$a_configuration = $this->getConfiguration();
		
		$a_fields = [];
		if($s_type == 'basic' || $s_type == 'combined') {
			$a_fields = $a_configuration['basicDefinition']['fields']??[];
		}
		if($s_type == 'advanced' || $s_type == 'combined') {
			$a_fields = array_merge($a_fields, $a_configuration['advancedDefinition']['fields']??[]);
		}
		
		$a_formDefinition = [
			'layout' => [
				'bootstrap4' => [
					'break' => [],
				],
			],
			'fields' => $a_fields,
		];
		
		if($s_type != 'advanced' && $a_configuration['category'] !== 'static') {
			array_push($a_formDefinition['fields'], [
				'component' => 'reef:text_line',
				'name' => 'name',
				'required' => true,
				'regexp' => \Reef\Reef::NAME_REGEXP,
				'locales' => $this->Reef->transMultipleLocales(['title' => 'builder_field_name'], $this->Reef->getOption('locales')),
			]);
			
			array_push($a_formDefinition['fields'], [
				'component' => 'reef:hidden',
				'name' => 'old_name',
			]);
		}
		
		return $this->Reef->newValidTempForm($a_formDefinition);
	}
	
	/**
	 * Generate the basic locale form
	 * @param string $s_locale The locale to generate the form for
	 * @return TempForm
	 */
	public function generateBasicLocaleForm(string $s_locale) {
		return $this->generateLocaleForm(array_keys($this->getConfiguration()['basicLocale']??[]), $s_locale);
	}
	
	/**
	 * Generate the advanced locale form
	 * @param string $s_locale The locale to generate the form for
	 * @return TempForm
	 */
	public function generateAdvancedLocaleForm(string $s_locale) {
		return $this->generateLocaleForm(array_keys($this->getConfiguration()['advancedLocale']??[]), $s_locale);
	}
	
	/**
	 * Generate a locale form
	 * @param string[] $a_keys The locale keys
	 * @param string $s_locale The locale to generate the form for
	 * @return TempForm
	 */
	private function generateLocaleForm(array $a_keys, string $s_locale) {
		$a_configuration = $this->getConfiguration();
		
		$a_localeConfig = $a_configuration['locale'];
		$a_locale = $this->getLocale($s_locale);
		
		$a_fields = [];
		foreach($a_keys as $s_name) {
			$s_val = $a_locale[$s_name]??'';
			if(isset($a_localeConfig[$s_name]['title'])) {
				if(is_array($a_localeConfig[$s_name]['title'])) {
					$s_title = $a_localeConfig[$s_name]['title'][$s_locale]
					        ?? $a_localeConfig[$s_name]['title']['en_US']
					        ?? $a_localeConfig[$s_name]['title']['_no_locale']
					        ?? '';
				}
				else {
					$s_title = $a_localeConfig[$s_name]['title'];
				}
			}
			else {
				$s_title = $a_locale[$a_localeConfig[$s_name]['title_key']]??'';
			}
			
			$a_fields[] = [
				'component' => (($a_localeConfig[$s_name]['type']??'') == 'textarea') ? 'reef:textarea' : 'reef:text_line',
				'name' => $s_name,
				'locale' => [
					'title' => $s_title,
				],
				'default' => $s_val,
			];
		}
		
		$a_localeDefinition = [
			'locale' => [],
			'layout' => [
				'bootstrap4' => [
					'break' => [],
				],
			],
			'fields' => $a_fields,
		];
		
		return $this->Reef->newValidTempForm($a_localeDefinition);
	}
	
	/**
	 * Perform an internal request
	 * @param string $s_requestHash The hash containing the action to perform
	 * @param array $a_options Array with options
	 */
	public function internalRequest(string $s_requestHash, array $a_options = []) {
		throw new \Reef\Exception\InvalidArgumentException('Field does not implement internal requests');
	}
	
}
