<?php

namespace Reef\Components\Checkbox;

use Reef\Components\Component;
use \Reef\Components\Traits\Required\RequiredComponentInterface;
use \Reef\Components\Traits\Required\RequiredComponentTrait;

class CheckboxComponent extends Component implements RequiredComponentInterface {
	
	use RequiredComponentTrait;
	
	const COMPONENT_NAME = 'reef:checkbox';
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
	public function supportedStorages() : ?array {
		return [
			'pdo_mysql',
			'pdo_sqlite',
		];
	}
}
