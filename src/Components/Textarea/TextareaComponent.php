<?php

namespace Reef\Components\Textarea;

use Reef\Components\Component;

class TextareaComponent extends Component {
	
	const COMPONENT_NAME = 'reef:textarea';
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
