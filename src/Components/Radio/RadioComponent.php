<?php

namespace Reef\Components\Radio;

use Reef\Components\AbstractSingleChoice\AbstractSingleChoiceComponent;
use \Reef\Components\Traits\Required\RequiredComponentInterface;
use \Reef\Components\Traits\Required\RequiredComponentTrait;

class RadioComponent extends AbstractSingleChoiceComponent implements RequiredComponentInterface {
	
	use RequiredComponentTrait;
	
	const COMPONENT_NAME = 'reef:radio';
	const PARENT_NAME = null;
	
	/**
	 * @inherit
	 */
	public static function getDir() : string {
		return __DIR__.'/';
	}
	
}
