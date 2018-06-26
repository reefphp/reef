<?php

namespace Reef\Components\Heading;

use Reef\Components\Component;

class HeadingComponent extends Component {
	
	const COMPONENT_NAME = 'reef:heading';
	const PARENT_NAME = null;
	
	/**
	 * @inherit
	 */
	public static function getDir() : string {
		return __DIR__.'/';
	}
	
}