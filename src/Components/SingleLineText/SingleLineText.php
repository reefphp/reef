<?php

namespace Reef\Components\SingleLineText;

use Reef\Components\Component;
use Reef\Form;

class SingleLineText extends Component {
	
	const COMPONENT_NAME = 'reef:single_line_text';
	
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
		return [
			[
				'type' => 'local',
				'path' => 'script.js',
			]
		];
	}
	
	/**
	 * @inherit
	 */
	public function getCSS() : array {
		return [
			[
				'type' => 'local',
				'path' => 'style.css',
			]
		];
	}
	
	/**
	 * @inherit
	 */
	public function newValue() : SingleLineTextValue {
		return new SingleLineTextValue($this);
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
		$a_vars['value'] = (string)$Value->toTemplateVar();
		return $a_vars;
	}
}
