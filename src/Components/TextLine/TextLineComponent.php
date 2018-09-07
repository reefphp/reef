<?php

namespace Reef\Components\TextLine;

use Reef\Components\Component;
use \Reef\Components\Traits\Required\RequiredComponentInterface;
use \Reef\Components\Traits\Required\RequiredComponentTrait;

class TextLineComponent extends Component implements RequiredComponentInterface {
	
	use RequiredComponentTrait;
	
	const COMPONENT_NAME = 'reef:text_line';
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
			if(@preg_match('/'.str_replace('/', '\\/', $a_declaration['regexp']).'/', null) === false) {
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
				'path' => 'form.js',
				'view' => 'form',
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
				'path' => 'form.css',
				'view' => 'form',
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
			'pdo_mysql',
			'pdo_sqlite',
		];
	}
	
}
