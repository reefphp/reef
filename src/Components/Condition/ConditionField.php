<?php

namespace Reef\Components\Condition;

use Reef\Components\Field;

class ConditionField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [[
			'type' => \Reef\Storage\Storage::TYPE_TEXT,
		]];
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
	public function updateDataLoss($OldField) {
		return \Reef\Updater::DATALOSS_NO;
	}
	
	/**
	 * @inherit
	 */
	public function view_form($Value, $a_options = []) : array {
		$a_vars = parent::view_form($Value, $a_options);
		$a_vars['value'] = $Value->toTemplateVar();
		return $a_vars;
	}
}
