<?php

namespace Reef\Components\Paragraph;

use Reef\Components\Field;

class ParagraphField extends Field {
	
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
		return new ParagraphValue($this);
	}
}
