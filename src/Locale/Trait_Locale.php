<?php

namespace Reef\Locale;

/**
 * Trait providing general locale functionality
 */
trait Trait_Locale {
	
	/**
	 * Cache of combined locales
	 * @type string[][]
	 */
	private $a_locales = [];
	
	/**
	 * Cache of locale sources
	 * @type string[][]
	 */
	private $a_localeSources = [];
	
	/**
	 * Retrieve base locale array from file system
	 * @param string $s_locale The locale to fetch
	 * @return string[] The locale data
	 */
	abstract protected function fetchBaseLocale($s_locale) : array;
	
	/**
	 * Return an array of language replacements: key/value pairs of
	 * variables to be used in translations
	 * @return array
	 */
	protected function getLanguageReplacements() : array {
		return [];
	}
	
	/**
	 * Return an array of all locale keys that can be used
	 * @return string[]
	 */
	protected function getLocaleKeys() : array {
		return [];
	}
	
	/**
	 * Return the default locale to use
	 * @return ?string
	 */
	abstract protected function getDefaultLocale() : ?string;
	
	/**
	 * Combine multiple locale sources by using combineLocaleSources() with
	 * multiple invocations of getOwnLocaleSource() and/or getCombinedLocaleSources()
	 * on different related objects
	 * @return string[]
	 */
	public function getCombinedLocaleSources($s_locale) : array {
		return $this->getOwnLocaleSource($s_locale);
	}
	
	/**
	 * Temporarily overwrite some locale
	 * @param string $s_locale The locale key to use
	 * @param string[] $a_locale The locale
	 */
	public function setLocale($s_locale, $a_locale) {
		$this->getOwnLocaleSource($s_locale);
		$this->a_localeSources[$s_locale] = array_merge($this->a_localeSources[$s_locale], $a_locale);
	}
	
	/**
	 * Retrieve & cache a local locale source
	 * @param string $s_locale The locale to fetch
	 * @return string[] The locale data
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
	
	/**
	 * Combine multiple locale sources into a single locale array
	 * This function tries to find a translation for each locale key in the provided locale sources,
	 * and stops searching for a translation as soon as one is found, continuing to the next locale key.
	 * @param string[] $a_baseLocale The base locale, with base values and including all keys to search for
	 * @param string[] ...$a_mergeLocales Any other locale sources
	 * @return string[] The resulting locale array
	 */
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
	 * @param null|string|string[] $a_locales The locale to fetch, or null for default locale. If you provide multiple locales, the first available locale will be fetched
	 * @return string[] The locale data
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
		
		// Fetch first locale, and determine missing keys compared to the english (reference) locale
		$s_firstLocale = array_shift($a_locales);
		$a_locale = $this->getCombinedLocaleSources($s_firstLocale);
		$a_allKeys = ($s_firstLocale == 'en_US') ? array_keys($a_locale) : array_keys($this->getCombinedLocaleSources('en_US'));
		$a_missing = [];
		foreach($a_allKeys as $s_key) {
			if(!isset($a_locale[$s_key])) {
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
	
	/**
	 * Get a single locale translation
	 * @param string $s_langKey The language key to retrieve
	 * @param ?(string[]) $a_locales The locales to use, in order of precedence
	 * @return ?string The value, or null if it is not set
	 */
	public function trans($s_langKey, $a_locales = null) {
		return $this->getLocale($a_locales)[$s_langKey]??null;
	}
	
	/**
	 * Retrieve multiple locale translations
	 * @param string[] $a_langKeys The language keys to retrieve
	 * @param ?(string[]) $a_locales The locales to use, in order of precedence
	 * @return (?string)[] The values
	 */
	public function transMultiple($a_langKeys, $a_locales = null) {
		$a_locale = $this->getLocale($a_locales);
		
		$a_trans = [];
		foreach($a_langKeys as $m_key => $s_langKey) {
			$m_key = is_string($m_key) ? $m_key : $s_langKey;
			$a_trans[$m_key] = $a_locale[$s_langKey]??null;
		}
		
		return $a_trans;
	}
	
	/**
	 * Retrieve multiple locale translations, for multiple locales
	 * @param string[] $a_langKeys The language keys to retrieve
	 * @param string[] $a_locales The locales to retrieve
	 * @return (?string)[][] The values, indexed by locale and language-key, respectively
	 */
	public function transMultipleLocales($a_langKeys, $a_locales) {
		$a_trans = [];
		foreach($a_locales as $s_locale) {
			$a_trans[$s_locale] = $this->transMultiple($a_langKeys, $s_locale);
		}
		return $a_trans;
	}
	
}
