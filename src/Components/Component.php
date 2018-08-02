<?php

namespace Reef\Components;

use Reef\Form;
use Reef\Reef;
use Reef\Trait_Locale;
use Symfony\Component\Yaml\Yaml;

abstract class Component {
	
	use Trait_Locale;
	
	protected $Reef;
	protected $a_configuration;
	
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
	public function getReef() {
		return $this->Reef;
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
		foreach($a_configuration['definition']['fields']??[] as $a_field) {
			$a_components[$a_field['component']] = 1;
		}
		
		unset($a_components[static::COMPONENT_NAME]);
		
		return array_keys($a_components);
	}
	
	/**
	 * Returns an array of supported layouts
	 * @return array
	 */
	abstract public function supportedLayouts() : array;
	
	/**
	 * Return a list of class names, of this class and all its parents, except this abstract Component class
	 * @return string The directory
	 */
	public function getInheritanceList() {
		$s_currentClass = get_called_class();
		$a_classes = array_merge([$s_currentClass], class_parents($s_currentClass));
		array_pop($a_classes); // Remove the Component class
		return array_values($a_classes);
	}
	
	/**
	 * Returns the raw HTML template of this component
	 * @param ?string $s_layout The layout to use. Optional, defaults to the layout set in the Reef setup
	 * @return string The template
	 */
	public function getTemplate($s_layout = null) {
		if(empty($s_layout)) {
			$s_layout = $this->Reef->getSetup()->getLayout()->getName();
		}
		
		$s_templateDir = null;
		$s_viewfile = 'view/'.$s_layout.'/form.mustache';
		
		$a_classes = $this->getInheritanceList();
		foreach($a_classes as $s_class) {
			if(file_exists($s_class::getDir() . $s_viewfile)) {
				$s_templateDir = $s_class::getDir();
				break;
			}
		}
		
		if($s_templateDir === null) {
			throw new ResourceNotFoundException("Could not find form template file for component '".static::COMPONENT_NAME."'.");
		}
		
		return file_get_contents($s_templateDir.$s_viewfile);
	}
	
	/**
	 * Return the configuration array of this component
	 * @return array
	 */
	public function getConfiguration() : array {
		if($this->a_configuration !== null) {
			return $this->a_configuration;
		}
		
		$this->a_configuration = $this->Reef->cache('configuration.component.'.static::COMPONENT_NAME, function() {
			$a_configuration = Yaml::parseFile(static::getDir().'config.yml');
			
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
			
			[$a_configuration['definition']['fields'], $a_configuration['props']] = $this->mergeConfigurationFieldsProps(
				$a_configuration['definition']['fields'],
				$a_configuration['props'],
				$a_parentConfiguration['definition']['fields'],
				$a_parentConfiguration['props']
			);
			
			return $a_configuration;
		});
		
		return $this->a_configuration;
	}
	
	/**
	 * Merge configuration fields & properties of this component with its parents fields & properties
	 * @param array $a_fields
	 * @param array $a_props
	 * @param array $a_parentFields
	 * @param array $a_parentProps
	 * @return [array, array] merged fields & props
	 */
	private function mergeConfigurationFieldsProps($a_fields, $a_props, $a_parentFields, $a_parentProps) {
		
		// Create mapping from name to position
		$a_parentFieldsKeys = [];
		foreach($a_parentFields as $i_pos => $a_field) {
			if(isset($a_field['name'])) {
				$a_parentFieldsKeys[$a_field['name']] = $i_pos;
			}
		}
		
		$a_parentPropsKeys = array_flip(array_column($a_parentProps, 'name'));
		
		// We take the parent fields & props as base
		$a_returnFields = $a_parentFields;
		$a_returnProps = $a_parentProps;
		
		// Merge new fields
		foreach($a_fields as $a_field) {
			if(!isset($a_field['name'])) {
				$a_returnFields[] = $a_field;
				continue;
			}
			
			if(isset($a_parentPropsKeys[$a_field['name']])) {
				unset($a_returnProps[$a_parentPropsKeys[$a_field['name']]]);
			}
			
			if(isset($a_parentFieldsKeys[$a_field['name']])) {
				$a_returnFields[$a_parentFieldsKeys[$a_field['name']]] = $a_field;
			}
			else {
				$a_returnFields[] = $a_field;
			}
		}
		
		// Merge new props
		foreach($a_props as $a_prop) {
			if(isset($a_parentFieldsKeys[$a_prop['name']])) {
				unset($a_returnFields[$a_parentFieldsKeys[$a_prop['name']]]);
			}
			
			if(isset($a_parentPropsKeys[$a_prop['name']])) {
				$a_returnProps[$a_parentPropsKeys[$a_prop['name']]] = $a_prop;
			}
			else {
				$a_returnProps[] = $a_prop;
			}
		}
		
		// Use array_values() to make sure we don't leave gaps in the keys
		return [array_values($a_returnFields), array_values($a_returnProps)];
	}
	
	/**
	 * Get names of this component's props
	 * @return array<string>
	 */
	public function getPropNames() {
		$a_props = $this->getConfiguration()['props']??[];
		
		return array_column($a_props, 'name');
	}
	
	/**
	 * Returns an array of javascript files required by this component.
	 * Each file is defined by an array:
	 * [
	 *   type => local or remote
	 *   path => path or url
	 *   name => canonical name (required for remote files)
	 *   integrity => Optionally, an integrity value for remote files
	 * ]
	 * @return array The javascript files
	 */
	public function getJS() {
		return [];
	}
	
	/**
	 * Returns an array of CSS files required by this component.
	 * Each file is defined by an array:
	 * [
	 *   type => local or remote
	 *   path => path or url
	 *   name => canonical name (required for remote files)
	 *   integrity => Optionally, an integrity value for remote files
	 * ]
	 * @return array The CSS files
	 */
	public function getCSS() {
		return [];
	}
	
	protected function fetchBaseLocale($s_locale) {
		return $this->Reef->cache('locale.component.base.'.static::COMPONENT_NAME.'.'.$s_locale, function() use($s_locale) {
			
			if(file_exists(static::getDir().'locale/'.$s_locale.'.yml')) {
				return Yaml::parseFile(static::getDir().'locale/'.$s_locale.'.yml')??[];
			}
			
			return [];
		});
	}
	
	protected function getLocaleKeys() {
		$a_configuration = $this->getConfiguration();
		return array_merge(array_filter(array_keys($a_configuration['locale'])), array_column($a_configuration['locale'], 'title_key'));
	}
	
	private function combineOwnAndParentLocaleSource($s_locale) {
		
		$ParentComponent = $this->getParent();
		if(!empty($ParentComponent)) {
			return $this->combineLocaleSources($this->getOwnLocaleSource($s_locale), $ParentComponent->combineOwnAndParentLocaleSource($s_locale));
		}
		
		return $this->getOwnLocaleSource($s_locale);
	}
	
	public function getCombinedLocaleSources($s_locale) {
		return $this->combineLocaleSources(
			$this->combineOwnAndParentLocaleSource($s_locale),
			\Reef\array_subset($this->Reef->getOwnLocaleSource($s_locale), $this->getLocaleKeys())
		);
	}
	
	protected function getDefaultLocale() {
		return $this->getReef()->getOption('default_locale');
	}
	
	public function generateBasicDeclarationForm() {
		return $this->generateDeclarationForm('basic');
	}
	
	public function generateAdvancedDeclarationForm() {
		return $this->generateDeclarationForm('advanced');
	}
	
	public function generateCombinedDeclarationForm() {
		return $this->generateDeclarationForm('combined');
	}
	
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
					'col_left' => 'col-12',
					'col_right' => 'col-12',
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
				'locales' => [
					'en_US' => [
						'title' => 'Field name',
					],
					'nl_NL' => [
						'title' => 'Veldnaam',
					],
				],
			]);
			
			array_push($a_formDefinition['fields'], [
				'component' => 'reef:hidden',
				'name' => 'old_name',
			]);
		}
		
		$DeclarationForm = $this->Reef->newTempForm();
		$DeclarationForm->importValidatedDefinition($a_formDefinition);
		
		return $DeclarationForm;
	}
	
	public function generateBasicLocaleForm(string $s_locale) {
		return $this->generateLocaleForm(array_keys($this->getConfiguration()['basicLocale']??[]), $s_locale);
	}
	
	public function generateAdvancedLocaleForm(string $s_locale) {
		return $this->generateLocaleForm(array_keys($this->getConfiguration()['advancedLocale']??[]), $s_locale);
	}
	
	private function generateLocaleForm(array $a_keys, string $s_locale) {
		if(empty($s_locale)) {
			$s_locale = '-';
		}
		$a_configuration = $this->getConfiguration();
		
		$a_localeConfig = $a_configuration['locale'];
		$a_locale = $this->getLocale($s_locale);
		
		$a_fields = [];
		foreach($a_keys as $s_name) {
			$s_val = $a_locale[$s_name]??'';
			$s_title = $a_locale[$a_localeConfig[$s_name]['title_key']]??'';
			
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
					'col_left' => 'col-12',
					'col_right' => 'col-12',
				],
			],
			'fields' => $a_fields,
		];
		
		$LocaleForm = $this->Reef->newTempForm();
		$LocaleForm->importValidatedDefinition($a_localeDefinition);
		
		return $LocaleForm;
	}
	
}
