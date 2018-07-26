<?php

namespace Reef\Components\SingleLineText;

use Reef\Components\Component;

class SingleLineTextComponent extends Component {
	
	const COMPONENT_NAME = 'reef:single_line_text';
	const PARENT_NAME = null;
	
	/**
	 * @inherit
	 */
	public static function getDir() : string {
		return __DIR__.'/';
	}
	
	/**
	 * @inherit
	 */
	public function validateDeclaration(array $a_declaration, array &$a_errors = null) : bool {
		$b_valid = true;
		
		if(isset($a_declaration['regexp'])) {
			if(preg_match('/'.str_replace('/', '\\/', $a_declaration['regexp']).'/', null) === false) {
				$a_errors['regexp'] = "Invalid regexp '".$a_declaration['regexp']."'";
				$b_valid = false;
			}
		}
		
		return $b_valid;
	}
	
	/**
	 * @inherit
	 */
	public function getJS() : array {
		return [
			[
				'type' => 'local',
				'path' => self::getDir().'script.js',
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
				'path' => self::getDir().'style.css',
			]
		];
	}
	
	/**
	 * @inherit
	 */
	public function supportedLayouts() : array {
		return [
			'bootstrap4',
		];
	}
	
}
