<?php

namespace Reef\Locale;

use \Reef\Reef;
use Symfony\Component\Yaml\Yaml;

/**
 * Trait providing locale functionality for components
 * 
 * Locale is combined from these sources, in order:
 *  - component custom					setLocale()
 *  - component							file: component locale/*.yml
 * 
 *  - component parents custom			setLocale()
 *  - component parents					file: component locale/*.yml
 * 
 *  - general reef custom				setLocale()
 *  - general reef						file: locale/*.yml
 *
 * The used keys are the keys and values of the basicLocale and advancedLocale arrays in the declaration, and the keys of the internalLocale array
 */

trait Trait_ComponentLocale {
	
	use Trait_Locale;
	
	abstract public function getReef() : Reef;
	abstract public function getParent();
	abstract public function getConfiguration() : array;
	
	protected function fetchBaseLocale($s_locale) {
		return $this->getReef()->cache('locale.component.base.'.static::COMPONENT_NAME.'.'.$s_locale, function() use($s_locale) {
			
			if(is_file(static::getDir().'locale/'.$s_locale.'.yml')) {
				return Yaml::parseFile(static::getDir().'locale/'.$s_locale.'.yml')??[];
			}
			
			return [];
		});
	}
	
	protected function getLocaleKeys() {
		$a_configuration = $this->getConfiguration();
		return array_merge(
			array_filter(array_keys($a_configuration['locale'])),
			array_column($a_configuration['locale'], 'title_key'),
			array_keys($a_configuration['internalLocale'])
		);
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
			\Reef\array_subset($this->getReef()->getOwnLocaleSource($s_locale), $this->getLocaleKeys())
		);
	}
	
	protected function getDefaultLocale() {
		return $this->getReef()->getOption('default_locale');
	}
	
}
