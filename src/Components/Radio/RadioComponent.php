<?php

namespace Reef\Components\Radio;

use Reef\Components\Component;

class RadioComponent extends Component {
	
	const COMPONENT_NAME = 'reef:radio';
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
