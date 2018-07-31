<?php

namespace Reef\Components\Textarea;

use Reef\Components\Field;
use Reef\Updater;

class TextareaField extends Field {
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [[
			'type' => \Reef\Storage\Storage::TYPE_TEXT,
			'limit' => $this->a_declaration['max_length'] ?? 15000,
		]];
	}
	
	/**
	 * @inherit
	 */
	public function newValue() {
		return new TextareaValue($this);
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
	public function view_form($Value, $a_options = []) : array {
		$a_vars = parent::view_form($Value, $a_options);
		$a_vars['value'] = (string)$Value->toTemplateVar();
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function updateDataLoss($OldField) {
		if(($OldField->a_declaration['max_length'] ?? 15000) > ($this->a_declaration['max_length'] ?? 15000)) {
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
