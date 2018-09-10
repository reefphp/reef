<?php

namespace Reef\Components\Select;

use Reef\Components\AbstractSingleChoice\AbstractSingleChoiceComponent;

class SelectComponent extends AbstractSingleChoiceComponent {
	
	const COMPONENT_NAME = 'reef:select';
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
	public function supportedLayouts() : array {
		return [
			'bootstrap4',
		];
	}
	
}
