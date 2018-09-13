<?php

namespace Reef\Components\TextNumber;

use Reef\Components\Field;
use Reef\Updater;
use \Reef\Components\Traits\Required\RequiredFieldInterface;
use \Reef\Components\Traits\Required\RequiredFieldTrait;

class TextNumberField extends Field implements RequiredFieldInterface {
	
	use RequiredFieldTrait;
	
	/**
	 * @inherit
	 */
	public function validateDeclaration(array &$a_errors = null) : bool {
		return parent::validateDeclaration($a_errors) && $this->validateDeclaration_required($a_errors);
	}
	
	private function is_integer_var(string $s_var) {
		if($s_var === '') {
			// An empty value is more like an int than like a float...
			return true;
		}
		
		if(substr($s_var, 0, 1) == '-') {
			$s_var = substr($s_var, 1);
		}
		
		return ctype_digit($s_var);
	}
	
	public function is_integer() {
		if(array_key_exists('step', $this->a_declaration) && !$this->is_integer_var($this->a_declaration['step'])) {
			return false;
		}
		
		if(!array_key_exists('min', $this->a_declaration)) {
			return true;
		}
		
		if(!$this->is_integer_var($this->a_declaration['min'])) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		
		if($this->is_integer()) {
			return [[
				'type' => \Reef\Storage\Storage::TYPE_INTEGER,
				'min' => $this->a_declaration['min']??null,
				'max' => $this->a_declaration['max']??null,
			]];
		}
		else {
			return [[
				'type' => \Reef\Storage\Storage::TYPE_FLOAT,
			]];
		}
	}
	
	/**
	 * @inherit
	 */
	public function getOverviewColumns() : array {
		return [
			$this->trans('title'),
		];
	}
	
	/**
	 * @inherit
	 */
	public function view_form($Value, $a_options = []) : array {
		$a_vars = parent::view_form($Value, $a_options);
		$a_vars['value'] = $Value->toTemplateVar();
		$a_vars['hasMin'] = isset($this->a_declaration['min']) && strlen($this->a_declaration['min']) > 0;
		$a_vars['hasMax'] = isset($this->a_declaration['max']) && strlen($this->a_declaration['max']) > 0;
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function view_submission($Value, $a_options = []) : array {
		$a_vars = parent::view_submission($Value, $a_options);
		$a_vars['value'] = $Value->toTemplateVar();
		$a_vars['hasValue'] = $a_vars['value'] !== '' && $a_vars['value'] !== null;
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function updateDataLoss($OldField) {
		if(isset($this->a_declaration['max']) && (!isset($OldField->a_declaration['max']) || $OldField->a_declaration['max'] > $this->a_declaration['max'])) {
			return Updater::DATALOSS_POTENTIAL;
		}
		
		if(isset($this->a_declaration['min']) && (!isset($OldField->a_declaration['min']) || $OldField->a_declaration['min'] < $this->a_declaration['min'])) {
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
		
		switch($a_data['storageFactoryName']) {
			case \Reef\Storage\PDO_SQLite_StorageFactory::getName():
			case \Reef\Storage\PDO_MySQL_StorageFactory::getName():
				if(isset($a_newDecl['max']) && (!isset($this->a_declaration['max']) || $this->a_declaration['max'] > $a_newDecl['max'])) {
					$a_data['content_updater']('UPDATE %1$s SET %2$s = ? WHERE %2$s > ?', [$a_newDecl['max'], $a_newDecl['max']]);
				}
				
				if(isset($a_newDecl['min']) && (!isset($this->a_declaration['min']) || $this->a_declaration['min'] < $a_newDecl['min'])) {
					$a_data['content_updater']('UPDATE %1$s SET %2$s = ? WHERE %2$s < ?', [$a_newDecl['min'], $a_newDecl['min']]);
				}
			break;
		}
	}
	
	protected function getLanguageReplacements() : array {
		return \Reef\array_subset($this->a_declaration, ['min', 'max']);
	}
}
