<?php

namespace Reef\Components\AbstractSingleChoice;

use Reef\Components\Field;
use Reef\Updater;

abstract class AbstractSingleChoiceField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		$i_length = 1;
		if(count($this->a_declaration['options']) > 0) {
			$i_length = max(array_map('strlen', array_column($this->a_declaration['options'], 'name')));
		}
		
		return [[
			'type' => \Reef\Storage\Storage::TYPE_TEXT,
			'limit' => $i_length,
		]];
	}
	
	/**
	 * @inherit
	 */
	public function isRequired() {
		return false;
	}
	
	/**
	 * @inherit
	 */
	public function view_form($Value, $a_options = []) : array {
		$a_vars = parent::view_form($Value, $a_options);
		$a_vars['value'] = (string)$Value->toTemplateVar();
		
		$s_selectedName = (string)$Value->toStructured();
		$a_opts = [];
		
		$Reef = $this->getComponent()->getReef();
		$a_langs = $Reef->getOption('locales');
		array_unshift($a_langs, $Reef->getOption('default_locale'));
		$a_langs = array_unique($a_langs);
		
		foreach($a_vars['options']??[] as $i => $a_option) {
			
			$s_title = '';
			foreach($a_langs as $s_lang) {
				if(!empty($a_option['locale'][$s_lang])) {
					$s_title = $a_option['locale'][$s_lang];
					break;
				}
			}
			
			$a_opts[$i] = [
				'name' => $a_option['name'],
				'default' => (!empty($s_selectedName)) ? $a_option['name'] == $s_selectedName : ($a_option['default']??false),
				'title' => $s_title,
			];
		}
		
		$a_vars['options'] = $a_opts;
		
		return $a_vars;
	}
	
	private function getOptionUpdatePlan($OldField, $NewField) {
		$a_oldNames = array_column($OldField->a_declaration['options'], 'name');
		$a_newNames = array_column($NewField->a_declaration['options'], 'name');
		
		$a_reNames = [];
		foreach($NewField->a_declaration['options'] as $a_option) {
			if(isset($a_option['old_name'])) {
				$a_reNames[$a_option['old_name']] = $a_option['name'];
			}
		}
		
		$a_update = $a_reNames;
		$a_create = array_diff($a_newNames, $a_oldNames, $a_reNames);
		$a_delete = array_diff($a_oldNames, $a_newNames, array_keys($a_reNames));
		
		return [$a_create, $a_update, $a_delete];
	}
	
	/**
	 * @inherit
	 */
	public function updateDataLoss($OldField) {
		[$a_create, $a_update, $a_delete] = $this->getOptionUpdatePlan($OldField, $this);
		
		if(count($a_delete) > 0) {
			return Updater::DATALOSS_POTENTIAL;
		}
		
		return Updater::DATALOSS_NO;
	}
	
	/**
	 * @inherit
	 */
	public function needsSchemaUpdate($OldField) {
		[$a_create, $a_update, $a_delete] = $this->getOptionUpdatePlan($OldField, $this);
		
		if(count($a_update) > 0 || count($a_delete) > 0) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @inherit
	 */
	public function beforeSchemaUpdate($a_data) {
		[$a_create, $a_update, $a_delete] = $this->getOptionUpdatePlan($this, $a_data['new_field']);
		
		foreach($a_update as $s_oldName => $s_newName) {
			switch($a_data['PDO_DRIVER']) {
				case 'sqlite':
				case 'mysql':
					$a_data['content_updater']('UPDATE %1$s SET %2$s = ? WHERE %2$s = ?', [$s_newName, $s_oldName]);
				break;
			}
		}
		
		$a_names = array_column($a_data['new_field']->a_declaration['options'], 'name');
		
		if(count($a_names) > 0) {
			$s_qs = str_repeat('?, ', count($a_names)-1).' ?';
			
			switch($a_data['PDO_DRIVER']) {
				case 'sqlite':
				case 'mysql':
					$a_data['content_updater']('UPDATE %1$s SET %2$s = NULL WHERE %2$s NOT IN ('.$s_qs.') ', array_values($a_names));
				break;
			}
		}
		else {
			switch($a_data['PDO_DRIVER']) {
				case 'sqlite':
				case 'mysql':
					$a_data['content_updater']('UPDATE %1$s SET %2$s = NULL');
				break;
			}
		}
	}
}
