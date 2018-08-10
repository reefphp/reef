<?php

namespace Reef\Components\Checkbox;

use Reef\Components\Field;

class CheckboxField extends Field {
	
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
		$s_title = $this->trans('title_left');
		$s_title = empty($s_title) ? $this->trans('title_right') : $s_title;
		return [
			$s_title,
		];
	}
	
	/**
	 * @inherit
	 */
	public function isRequired() {
		return (bool)($this->a_declaration['required']??false);
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
