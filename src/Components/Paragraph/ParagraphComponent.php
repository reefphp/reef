<?php

namespace Reef\Components\Paragraph;

use Reef\Components\Component;

class ParagraphComponent extends Component {
	
	const COMPONENT_NAME = 'reef:paragraph';
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
		return null;
	}
	
}
