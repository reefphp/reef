<?php

namespace Reef\Components\TextNumber;

use Reef\Components\Component;
use \Reef\Components\Traits\Required\RequiredComponentInterface;
use \Reef\Components\Traits\Required\RequiredComponentTrait;

class TextNumberComponent extends Component implements RequiredComponentInterface {
	
	use RequiredComponentTrait;
	
	const COMPONENT_NAME = 'reef:text_number';
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
		
		if(isset($a_declaration['min']) && isset($a_declaration['max'])) {
			if($a_declaration['max'] < $a_declaration['min']) {
				$a_errors['max'] = "Max is lower than min";
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
	public function supportedLayouts() : array {
		return [
			'bootstrap4',
		];
	}
	
	/**
	 * @inherit
	 */
	public function supportedStorages() : ?array {
		return [
			'mysql',
			'sqlite',
		];
	}
}
