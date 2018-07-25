<?php

namespace Reef\Components\Heading;

use Reef\Components\Field;

class HeadingField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [];
	}
	
	/**
	 * @inherit
	 */
	public function newValue() {
		return new HeadingValue($this);
	}
}
