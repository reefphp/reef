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
	public function getOverviewColumns() : array {
		return [];
	}
	
	/**
	 * @inherit
	 */
	public function view_form($Value, $a_options = []) : array {
		$a_vars = parent::view_form($Value, $a_options);
		$a_vars['size'] = $a_vars['size'] ?? 4;
		return $a_vars;
	}
	
}
