<?php

namespace Reef\Components;

use Reef\Form;
use Symfony\Component\Yaml\Yaml;

abstract class Field {
	
	protected $a_config;
	protected $Component;
	protected $Form;
	
	public function __construct(array $a_config, Form $Form, Component $Component) {
		$this->a_config = $a_config;
		$this->Form = $Form;
		$this->Component = $Component;
	}
	
	/**
	 * Return the Form the field is assigned to
	 * @return Form
	 */
	public function getForm() : Form {
		return $this->Form;
	}
	
	/**
	 * Return the Component of this Field
	 * @return Component
	 */
	public function getComponent() : Component {
		return $this->Component;
	}
	
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
	 * @param FieldValue $Value The value object
	 * @param array $a_options Options
	 * @return array The template variables
	 */
	public function view_form(FieldValue $Value, $a_options = []) : array {
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
			throw new \InvalidArgumentException("Could not find locale for field '".$this->a_config['name']."'.");
		}
		
		$a_classes = $this->Component->getInheritanceList();
		foreach($a_classes as $s_class) {
			// Find component-defined locale
			foreach($a_locales as $s_loc) {
				if(file_exists($s_class::getDir().'locale/'.$s_loc.'.yml')) {
					$a_locale = array_merge(
						Yaml::parseFile($s_class::getDir().'locale/'.$s_loc.'.yml')??[],
						$a_locale
					);
					break;
				}
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
