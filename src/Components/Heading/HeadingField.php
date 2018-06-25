<?php

namespace Reef\Components\Heading;

use Reef\Components\Field;

class HeadingField extends Field {
	
	/**
	 * @inherit
	 */
	public function newValue() {
		return new HeadingValue($this);
	}
	
	/**
	 * @inherit
	 */
	public function view_builder() : array {
		
		
	}
}
