<?php

namespace Reef\Components\Textarea;

use Reef\Components\Component;
use \Reef\Components\Traits\Required\RequiredComponentInterface;
use \Reef\Components\Traits\Required\RequiredComponentTrait;

class TextareaComponent extends Component implements RequiredComponentInterface {
	
	use RequiredComponentTrait;
	
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
	public function supportedStorages() : ?array {
		return [
			'pdo_mysql',
			'pdo_sqlite',
		];
	}
	
}
