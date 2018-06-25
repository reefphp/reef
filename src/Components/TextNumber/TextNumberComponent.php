<?php

namespace Reef\Components\TextNumber;

use Reef\Components\SingleLineText\SingleLineTextComponent;

class TextNumberComponent extends SingleLineTextComponent {
	
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
}
