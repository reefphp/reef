<?php

namespace Reef\Components;

use Reef\Form;
use Reef\Reef;
use Reef\Trait_Locale;
use Symfony\Component\Yaml\Yaml;

abstract class Component {
	
	use Trait_Locale;
	
	protected $Reef;
	
	/**
	 * Set the Reef object
	 * @param Reef $Reef The Reef we are currently working in
	 */
	public function setReef(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	/**
	 * Get the parent component
	 * @return Component The parent component
	 */
	public function getParent() {
		return (static::PARENT_NAME == null) ? null : $this->Reef->getSetup()->getComponent(static::PARENT_NAME);
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
			throw new \Exception("Could not find form template file for component '".static::COMPONENT_NAME."'.");
		}
		
		return file_get_contents($s_templateDir.$s_viewfile);
	}
	
	/**
	 * Return the configuration array of this component
	 * @return array
	 */
	public function getConfiguration() : array {
		return $this->Reef->cache('configuration.component.'.static::COMPONENT_NAME, function() {
			$a_configuration = Yaml::parseFile(static::getDir().'config.yml');
			$a_configuration['locale'] = array_merge($a_configuration['valueLocale']??[], $a_configuration['configLocale']??[]);
			
			$ParentComponent = $this->getParent();
			if(empty($ParentComponent)) {
				return $a_configuration;
			}
			
			$a_parentConfiguration = $ParentComponent->getConfiguration();
			
			$a_configuration['valueLocale'] = array_merge($a_parentConfiguration['valueLocale'], $a_configuration['valueLocale']??[]);
			$a_configuration['configLocale'] = array_merge($a_parentConfiguration['configLocale'], $a_configuration['configLocale']??[]);
			$a_configuration['locale'] = array_merge($a_configuration['valueLocale'], $a_configuration['configLocale']);
			$a_configuration['definition']['fields'] = array_merge($a_parentConfiguration['definition']['fields'], $a_configuration['definition']['fields']);
			
			return $a_configuration;
		});
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
	
	public function getLocaleKeys() {
		return array_keys($this->getConfiguration()['locale']);
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
	
}
