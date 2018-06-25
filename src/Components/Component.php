<?php

namespace Reef\Components;

use Reef\Form;
use Symfony\Component\Yaml\Yaml;

abstract class Component {
	
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
		return Yaml::parseFile(static::getDir().'definition.yml');
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
	
}
