<?php

namespace Reef\Components\Submit;

use Reef\Components\Field;

class SubmitField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [];
	}
	
	/**
	 * @inherit
	 */
	public function getOverviewColumns() : array {
		return [];
	}
	
}
