<?php

namespace Reef\Components\CheckList;

use Reef\Components\Component;

class CheckListComponent extends Component {
	
	const COMPONENT_NAME = 'reef:checklist';
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
