<?php

namespace Reef\Components;

use Reef\Trait_Locale;
use Reef\Form;
use Symfony\Component\Yaml\Yaml;

abstract class Field {
	
	use Trait_Locale;
	
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
	
	protected function fetchBaseLocale($s_locale) {
		if(!empty($s_locale) && isset($this->a_config['locales'][$s_locale])) {
			return $this->a_config['locales'][$s_locale];
		}
		else if(isset($this->a_config['locale'])) {
			return $this->a_config['locale'];
		}
		else {
			return [];
		}
	}
	
	protected function getLocaleKeys() {
		return $this->getComponent()->getDefinition()['locale'];
	}
	
	public function getCombinedLocaleSources($s_locale) {
		return $this->combineLocaleSources(
			$this->getOwnLocaleSource($s_locale),
			$this->getForm()->getOwnLocaleSource($s_locale),
			$this->getComponent()->getCombinedLocaleSources($s_locale)
		);
	}
	
	protected function getDefaultLocale() {
		return $this->getForm()->getFormConfig()['default_locale']??null;
	}
}
