<?php

namespace Reef\Components\OptionList;

use Reef\Components\Component;

class OptionListComponent extends Component {
	
	const COMPONENT_NAME = 'reef:option_list';
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
