<?php

namespace Reef\Components\CheckList;

use Reef\Components\Field;
use Reef\Updater;

class CheckListField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		$a_columns = [];
		
		foreach($this->a_declaration['options'] as $a_option) {
			$a_columns[$a_option['name']] = [
				'type' => \Reef\Storage\Storage::TYPE_BOOLEAN,
			];
		}
		
		return $a_columns;
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
		$a_vars['values'] = (array)$Value->toTemplateVar();
		
		$a_values = (array)$Value->toStructured();
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
				'default' => $a_values[$a_option['name']] ?? $a_option['default'],
				'title' => $s_title,
			];
		}
		
		$a_vars['options'] = $a_opts;
		
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function view_submission($Value, $a_options = []) : array {
		return $this->view_form($Value, $a_options);
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
		
		if(count($a_update) > 0) {
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
					$a_data['content_updater']('UPDATE %1$s SET '.$a_data['new_columns'][$s_newName].' = '.$a_data['old_columns'][$s_oldName].' ');
				break;
			}
		}
		
	}
}
