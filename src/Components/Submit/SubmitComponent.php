<?php

namespace Reef\Components\Submit;

use Reef\Components\Component;

class SubmitComponent extends Component {
	
	const COMPONENT_NAME = 'reef:submit';
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
	
	/**
	 * @inherit
	 */
	public function supportedStorages() : ?array {
		return null;
	}
	
}
