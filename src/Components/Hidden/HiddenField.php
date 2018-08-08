<?php

namespace Reef\Components\Hidden;

use Reef\Components\Field;

class HiddenField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [
		];
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
		$a_vars['value'] = (string)$Value->toTemplateVar();
		return $a_vars;
	}
}
