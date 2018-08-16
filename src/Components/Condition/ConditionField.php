<?php

namespace Reef\Components\Condition;

use Reef\Components\Field;

class ConditionField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [[
			'type' => \Reef\Storage\Storage::TYPE_BOOLEAN,
		]];
	}
	
	/**
	 * @inherit
	 */
	public function getOverviewColumns() : array {
		$s_title = $this->trans('title');
		return [
			$s_title,
		];
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
		$a_vars['value'] = (bool)$Value->toTemplateVar();
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function view_submission($Value, $a_options = []) : array {
		$a_vars = parent::view_submission($Value, $a_options);
		$a_vars['value'] = (bool)$Value->toTemplateVar();
		return $a_vars;
	}
}
