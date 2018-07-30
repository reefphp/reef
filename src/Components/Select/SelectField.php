<?php

namespace Reef\Components\Select;

use Reef\Components\Field;
use Reef\Updater;

class SelectField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		$i_length = max(array_map('strlen', $this->a_declaration['options']['names'])) ?? 1;
		
		return [[
			'type' => \Reef\Storage\Storage::TYPE_TEXT,
			'limit' => $i_length,
		]];
	}
	
	/**
	 * @inherit
	 */
	public function newValue() {
		return new SelectValue($this);
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
				'default' => (!empty($s_selectedName)) ? $a_option['name'] == $s_selectedName : $a_option['default'],
				'title' => $s_title,
			];
		}
		
		$a_vars['options'] = $a_opts;
		
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function updateDataLoss($OldField) {
		$a_oldNames = $OldField->a_declaration['options']['names'];
		$a_names = $this->a_declaration['options']['names'];
		
		if(count(array_diff($a_oldNames, $a_names)) > 0) {
			return Updater::DATALOSS_POTENTIAL;
		}
		
		return Updater::DATALOSS_NO;
	}
	
	/**
	 * @inherit
	 */
	public function beforeSchemaUpdate($a_data) {
		$NewField = $a_data['new_field'];
		$a_newDecl = $NewField->getDeclaration();
		
		switch($a_data['PDO_DRIVER']) {
			case 'sqlite':
			case 'mysql':
				if(isset($a_newDecl['max_length']) && (!isset($this->a_declaration['max_length']) || $this->a_declaration['max_length'] > $a_newDecl['max_length'])) {
					$a_data['content_updater']('UPDATE %1$s SET %2$s = SUBSTR(%2$s, 0, '.((int)$a_newDecl['max_length']).') WHERE LENGTH(%2$s) > '.((int)$a_newDecl['max_length']).' ');
				}
			break;
		}
	}
}
