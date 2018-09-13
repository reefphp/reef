<?php

namespace Reef\Components\Condition;

use Reef\Components\Component;

class ConditionComponent extends Component {
	
	const COMPONENT_NAME = 'reef:condition';
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
