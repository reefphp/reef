<?php

namespace Reef\Components\OptionList;

use Reef\Components\Field;
use Reef\Updater;

class OptionListField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [[
			'type' => \Reef\Storage\Storage::TYPE_TEXT,
			'limit' => 16000,
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
	public function view_form($Value, $a_options = []) : array {
		$a_vars = parent::view_form($Value, $a_options);
		$a_vars['value'] = (array)$Value->toTemplateVar();
		$a_vars['names'] = $a_vars['names'] ?? true;
		$a_vars['locales'] = $this->getComponent()->getReef()->getOption('locales');
		$a_vars['multipleLocales'] = count($a_vars['locales']) > 1;
		$a_vars['name_regexp'] = \Reef\Reef::NAME_REGEXP;
		$a_vars['options_base64'] = base64_encode(json_encode($a_vars['value']??[]));
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function updateDataLoss($OldField) {
		return Updater::DATALOSS_NO;
	}
	
	protected function getLanguageReplacements() : array {
		return \Reef\array_subset($this->a_declaration, ['min_num_options', 'max_num_options', 'max_checked_defaults']);
	}
}
