<?php

namespace Reef\Components;

use Reef\Form;
use Symfony\Component\Yaml\Yaml;

abstract class Component {
	
	protected $a_config;
	protected $Form;
	
	public function __construct(array $a_config, Form $Form) {
		$this->a_config = $a_config;
		$this->Form = $Form;
	}
	
	/**
	 * Return the base directory of the current component
	 * @return string The directory
	 */
	abstract public static function getDir() : string;
	
	/**
	 * Return the Form the component is assigned to
	 * @return Form
	 */
	public function getForm() : Form {
		return $this->Form;
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
	abstract public function getJS() : array;
	
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
	abstract public function getCSS() : array;
	
	/**
	 * Return the entire configuration array
	 * @return array
	 */
	public function getConfig() : array {
		return $this->a_config;
	}
	
	/**
	 * Build template variables for the form builder
	 * @return array The template variables
	 */
	abstract public function view_builder() : array;
	
	/**
	 * Build template variables for the form
	 * @param ComponentValue $Value The value object
	 * @param array $a_options Options
	 * @return array The template variables
	 */
	public function view_form(ComponentValue $Value, $a_options = []) : array {
		$a_vars = $this->a_config;
		
		$a_vars['errors'] = $Value->getErrors();
		$a_vars['hasErrors'] = !empty($a_vars['errors']);
		
		$a_vars['locale'] = $this->getLocale($a_options['locale']??null);
		unset($a_vars['locales']);
		
		return $a_vars;
	}
	
	/**
	 * Get locale array
	 * @param array|string $a_locales The locale to fetch, or null for default locale. If you provide multiple locales, the first available locale will be fetched
	 * @return array The locale data
	 */
	public function getLocale($a_locales = null) {
		$a_locale = null;
		
		if(!is_array($a_locales)) {
			$a_locales = [$a_locales];
		}
		
		// Build priority list of locales
		$a_locales[] = $this->getForm()->getFormConfig()['default_locale']??null;
		$a_locales[] = 'en_US';
		$a_locales = array_unique(array_filter($a_locales));
		
		// Find user-defined locale
		if(isset($this->a_config['locales'])) {
			foreach($a_locales as $s_loc) {
				if(isset($this->a_config['locales'][$s_loc])) {
					$a_locale = $this->a_config['locales'][$s_loc];
					break;
				}
			}
		}
		
		// Find user-defined general locale
		if($a_locale === null && isset($this->a_config['locale'])) {
			$a_locale = $this->a_config['locale'];
		}
		
		if($a_locale === null) {
			throw new \InvalidArgumentException("Could not find locale for component '".$this->a_config['name']."'.");
		}
		
		// Find component-defined locale
		foreach($a_locales as $s_loc) {
			if(file_exists(static::getDir().'locale/'.$s_loc.'.yml')) {
				$a_locale = array_merge(
					Yaml::parseFile(static::getDir().'locale/'.$s_loc.'.yml')??[],
					$a_locale
				);
				break;
			}
		}
		
		// Find Reef-defined locale
		$a_locale = array_merge(
			$this->getForm()->getReef()->getLocale($a_locales),
			$a_locale
		);
		
		return $a_locale;
	}
	
	public function trans($s_key, $a_locales = null) {
		return $this->getLocale($a_locales)[$s_key]??null;
	}
}
