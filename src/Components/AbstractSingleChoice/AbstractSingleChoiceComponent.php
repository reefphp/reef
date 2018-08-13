<?php

namespace Reef\Components\AbstractSingleChoice;

use Reef\Components\Component;

abstract class AbstractSingleChoiceComponent extends Component {
	
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
			'mysql',
			'sqlite',
		];
	}
	
}
