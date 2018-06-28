<?php

namespace Reef;

trait Trait_Locale {
	
	/**
	 * @note Locale is fetched in the following order:
	 *  - field custom
	 *  - field
	 *  - form custom
	 *  - form
	 *  - component custom
	 *  - component
	 *  - component parents custom
	 *  - component parents
	 *  - general/common
	 */
	
	private $a_locales = [];
	private $a_localeSources = [];
	
	/**
	 * Retrieve & cache base locale array from file system
	 * @param string $s_locale The locale to fetch
	 * @return array The locale data
	 */
	abstract protected function fetchBaseLocale($s_locale) : array;
	
	/**
	 * @override
	 */
	protected function getLanguageReplacements() : array {
		return [];
	}
	
	/**
	 * @override
	 */
	protected function getLocaleKeys() : array {
		return [];
	}
	
	/**
	 * @override
	 */
	protected function getDefaultLocale() : ?string {
		return null;
	}
	
	/**
	 * @override
	 */
	public function getCombinedLocaleSources($s_locale) : array {
		return $this->getOwnLocaleSource($s_locale);
	}
	
	public function setLocale($s_locale, $a_locale) {
		$this->getLocale($s_locale);
		$this->a_localeSources[$s_locale] = array_merge($this->a_localeSources[$s_locale], $a_locale);
	}
	
	/**
	 * Get current locale array
	 * @param string $s_locale The locale to fetch
	 * @return array The locale data
	 */
	public function getOwnLocaleSource($s_locale) {
		if(isset($this->a_localeSources[$s_locale])) {
			return $this->a_localeSources[$s_locale];
		}
		
		$a_localeKeys = $this->getLocaleKeys();
		$a_locale = array_combine($a_localeKeys, array_fill(0, count($a_localeKeys), null));
		
		$a_locale = array_merge(
			$a_locale,
			$this->fetchBaseLocale($s_locale)
		);
		
		$this->a_localeSources[$s_locale] = $a_locale;
		return $a_locale;
	}
	
	protected function combineLocaleSources($a_baseLocale, ...$a_mergeLocales) {
		foreach($a_baseLocale as $s_key => $s_val) {
			if($s_val === null) {
				foreach($a_mergeLocales as $a_mergeLocale) {
					if(isset($a_mergeLocale[$s_key])) {
						$a_baseLocale[$s_key] = $a_mergeLocale[$s_key];
						break;
					}
				}
			}
		}
		return $a_baseLocale;
	}
	
	/**
	 * Get locale array
	 * @param array $a_locales The locale to fetch, or null for default locale. If you provide multiple locales, the first available locale will be fetched
	 * @return array The locale data
	 */
	public function getLocale($a_locales = null) {
		if(!is_array($a_locales)) {
			$a_locales = [$a_locales];
		}
		
		// Build priority list of locales
		$a_locales[] = $this->getDefaultLocale();
		$a_locales[] = 'en_US';
		$a_locales = array_unique(array_filter($a_locales));
		
		$s_localesKey = implode('/', $a_locales);
		if(isset($this->a_locales[$s_localesKey])) {
			return $this->a_locales[$s_localesKey];
		}
		
		$s_firstLocale = array_shift($a_locales);
		$a_locale = $this->getCombinedLocaleSources($s_firstLocale);
		$a_missing = [];
		foreach($a_locale as $s_key => $s_val) {
			if($s_val === null) {
				$a_missing[$s_key] = 1;
			}
		}
		
		// Fill in gaps with other languages, if provided
		if(!empty($a_missing)) {
			foreach($a_locales as $s_locale) {
				$a_secLocale = array_intersect_key($this->getCombinedLocaleSources($s_locale), $a_missing);
				
				$a_locale = array_merge($a_locale, $a_secLocale);
				
				$a_missing = array_diff_key($a_missing, $a_secLocale);
				
				if(empty($a_missing)) {
					break;
				}
			}
		}
		
		// Replace variables
		$a_replacements = $this->getLanguageReplacements();
		if(!empty($a_replacements)) {
			foreach($a_locale as $s_key => $s_val) {
				if(empty($s_val)) {
					continue;
				}
				
				$a_locale[$s_key] = preg_replace_callback('/\[\[([^\[\]]+)\]\]/', function($a_match) use($a_replacements) {
					$a_parts = explode('.', $a_match[1]);
					
					foreach($a_parts as $s_part) {
						if(!is_array($a_replacements) || !isset($a_replacements[$s_part])) {
							return '';
						}
						
						$a_replacements = $a_replacements[$s_part];
					}
					
					// Most likely, $a_replacements has now become a string...
					return is_scalar($a_replacements) ? $a_replacements : '';
				}, $s_val);
			}
		}
		
		$this->a_locales[$s_localesKey] = $a_locale;
		return $a_locale;
	}
	
	
	public function trans($s_key, $a_locales = null) {
		return $this->getLocale($a_locales)[$s_key]??null;
	}
	
	
}
