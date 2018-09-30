<?php

namespace Reef\Components\Checkbox;

use Reef\Components\Field;
use \Reef\Components\Traits\Required\RequiredFieldInterface;
use \Reef\Components\Traits\Required\RequiredFieldTrait;

class CheckboxField extends Field implements RequiredFieldInterface {
	
	use RequiredFieldTrait;
	
	/**
	 * @inherit
	 */
	public function validateDeclaration(array &$a_errors = null) : bool {
		return parent::validateDeclaration($a_errors) && $this->validateDeclaration_required($a_errors);
	}
	
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
		$s_title = empty($s_title) ? $this->trans('box_label') : $s_title;
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
