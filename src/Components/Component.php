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
		return (static::PARENT_NAME == null) ? null : $this->Reef->getComponentMapper()->getComponent(static::PARENT_NAME);
	}
	
	/**
	 * Return a new field from this component
	 * @param array $a_config The config array for the field
	 * @param Form $Form The form the field belongs to
	 * @return Field
	 */
	public function newField(array $a_config, Form $Form) : Field {
		$s_class = substr(static::class, 0, -9).'Field';
		return new $s_class($a_config, $Form, $this);
	}
	
	/**
	 * Return the base directory of the current component
	 * @return string The directory
	 */
	abstract public static function getDir() : string;
	
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
	 * Return the definition array of this component
	 * @return array
	 */
	public function getDefinition() : array {
		return $this->Reef->cache('definition.component.'.static::COMPONENT_NAME, function() {
			$a_definition = Yaml::parseFile(static::getDir().'definition.yml');
			
			$ParentComponent = $this->getParent();
			if(empty($ParentComponent)) {
				return $a_definition;
			}
			
			$a_parentDefinition = $ParentComponent->getDefinition();
			
			$a_definition['locale'] = array_merge($a_definition['locale'], $a_parentDefinition['locale']);
			$a_definition['declaration']['fields'] = array_merge($a_parentDefinition['declaration']['fields'], $a_definition['declaration']['fields']);
			
			return $a_definition;
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
	
	protected function getLocaleKeys() {
		return $this->getDefinition()['locale'];
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
			\Reef\array_subset($this->Reef->getOwnLocaleSource($s_locale), $this->getDefinition()['locale'])
		);
	}
	
}
