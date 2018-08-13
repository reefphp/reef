<?php

namespace Reef\Locale;

/**
 * Trait providing locale functionality for fields
 * 
 * Locale is combined from these sources, in order:
 *  - field custom						setLocale()
 *  - field								declaration: locale/locales
 * 
 *  - form custom						setLocale()
 *  - form								definition: locale/locales
 * 
 *  - component custom					setLocale()
 *  - component							file: component locale/*.yml
 * 
 *  - component parents custom			setLocale()
 *  - component parents					file: component locale/*.yml
 * 
 *  - general reef custom				setLocale()
 *  - general reef						file: locale/*.yml
 *
 * The used keys are the keys of the basicLocale, advancedLocale & internalLocale arrays in the declaration
 */

trait Trait_FieldLocale {
	
	use Trait_Locale;
	
	abstract public function getDeclaration() : array;
	abstract public function getComponent() : Component;
	abstract public function getForm() : Form;
	
	protected function fetchBaseLocale($s_locale) {
		if(!empty($s_locale) && isset($this->getDeclaration()['locales'][$s_locale])) {
			return $this->getDeclaration()['locales'][$s_locale];
		}
		else if(isset($this->getDeclaration()['locale'])) {
			return $this->getDeclaration()['locale'];
		}
		else {
			return [];
		}
	}
	
	protected function getLocaleKeys() {
		$a_configuration = $this->getComponent()->getConfiguration();
		return array_keys(array_merge($a_configuration['locale'], $a_configuration['internalLocale']));
	}
	
	public function getCombinedLocaleSources($s_locale) {
		return $this->combineLocaleSources(
			$this->getOwnLocaleSource($s_locale),
			$this->getForm()->getOwnLocaleSource($s_locale),
			$this->getComponent()->getCombinedLocaleSources($s_locale)
		);
	}
	
	protected function getDefaultLocale() {
		return $this->getForm()->getDefinition()['default_locale']??$this->getComponent()->getReef()->getOption('default_locale');
	}
	
}
