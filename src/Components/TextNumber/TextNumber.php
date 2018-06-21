<?php

namespace Reef\Components\TextNumber;

use Reef\Components\SingleLineText\SingleLineText;

class TextNumber extends SingleLineText {
	
	const COMPONENT_NAME = 'reef:text_number';
	
	/**
	 * @inherit
	 */
	public static function getDir() : string {
		return __DIR__.'/';
	}
	
	/**
	 * @inherit
	 */
	public function getJS() : array {
		return array_merge(parent::getJS(), [
			[
				'type' => 'local',
				'path' => self::getDir().'script.js',
			]
		]);
	}
	
	/**
	 * @inherit
	 */
	public function newValue() {
		return new TextNumberValue($this);
	}
	
	/**
	 * @inherit
	 */
	public function view_builder() : array {
		
		
	}
	
	/**
	 * @inherit
	 */
	public function view_form($Value, $a_options = []) : array {
		$a_vars = parent::view_form($Value, $a_options);
		$a_vars['value'] = $a_vars['value'];
		$a_vars['hasMin'] = isset($this->a_config['min']);
		$a_vars['hasMax'] = isset($this->a_config['max']);
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function getLocale($a_locales = null) {
		$a_locale = parent::getLocale($a_locales);
		$b_min = isset($this->a_config['min']);
		$b_max = isset($this->a_config['max']);
		if($b_min && $b_max) {
			$a_locale['error_number_range'] = str_replace(['[min]', '[max]'], [$this->a_config['min'], $this->a_config['max']], $a_locale['error_number_min_max']);
		}
		else if($b_min) {
			$a_locale['error_number_range'] = str_replace('[min]', $this->a_config['min'], $a_locale['error_number_min']);
		}
		else if($b_max) {
			$a_locale['error_number_range'] = str_replace('[max]', $this->a_config['max'], $a_locale['error_number_max']);
		}
		else {
			$a_locale['error_number_range'] = '';
		}
		return $a_locale;
	}
}
