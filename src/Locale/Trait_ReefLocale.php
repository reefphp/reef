<?php

namespace Reef\Locale;

use Symfony\Component\Yaml\Yaml;

/**
 * Trait providing locale functionality for reef
 * 
 * Locale is combined from these sources, in order:
 *  - general reef custom				setLocale()
 *  - general reef						file: locale/*.yml
 *
 * The used keys are taken from the locale file
 */

trait Trait_ReefLocale {
	
	use Trait_Locale;
	
	abstract public function cache($s_cacheKey, $fn_val);
	abstract public function getOption($s_name);
	
	protected function fetchBaseLocale($s_locale) {
		return $this->cache('locale.reef.base.'.$s_locale, function() use($s_locale) {
			
			if(is_file(static::getDir().'locale/'.$s_locale.'.yml')) {
				return Yaml::parseFile(static::getDir().'locale/'.$s_locale.'.yml')??[];
			}
			
			return [];
		});
	}
	
	protected function getDefaultLocale() {
		return $this->getOption('default_locale');
	}
	
}
