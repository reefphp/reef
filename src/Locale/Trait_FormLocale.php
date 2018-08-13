<?php

namespace Reef\Locale;

/**
 * Trait providing locale functionality for forms
 * 
 * Locale is combined from these sources, in order:
 *  - form custom						setLocale()
 *  - form								definition: locale/locales
 * 
 *  - general reef custom				setLocale()
 *  - general reef						file: locale/*.yml
 *
 * The used keys are taken from the locale/locales definition
 */


trait Trait_FormLocale {
	
	use Trait_Locale;
	
	abstract public function getDefinition();
	abstract public function getReef();
	
	/**
	 * @inherit
	 */
	protected function fetchBaseLocale($s_locale) {
		if(!empty($s_locale) && isset($this->getDefinition()['locales'][$s_locale])) {
			return $this->getDefinition()['locales'][$s_locale];
		}
		else if(isset($this->getDefinition()['locale'])) {
			return $this->getDefinition()['locale'];
		}
		else {
			return [];
		}
	}
	
	/**
	 * @inherit
	 */
	public function getCombinedLocaleSources($s_locale) {
		return $this->combineLocaleSources(
			$this->getOwnLocaleSource($s_locale),
			$this->getReef()->getOwnLocaleSource($s_locale)
		);
	}
	
	/**
	 * @inherit
	 */
	protected function getDefaultLocale() {
		return $this->getDefinition()['default_locale']??$this->getReef()->getOption('default_locale');
	}
	
}
