<?php

namespace Reef\Components\TextLine;

use Reef\Components\Field;
use Reef\Updater;
use \Reef\Components\Traits\Required\RequiredFieldInterface;
use \Reef\Components\Traits\Required\RequiredFieldTrait;

class TextLineField extends Field implements RequiredFieldInterface {
	
	use RequiredFieldTrait;
	
	/**
	 * @inherit
	 */
	public function validateDeclaration(array &$a_errors = null) : bool {
		$b_valid = parent::validateDeclaration($a_errors);
		
		$b_valid = $this->validateDeclaration_required($a_errors) && $b_valid;
		
		return $b_valid;
	}
	
	public function getMaxLength() {
		if(!empty($this->a_declaration['max_length'])) {
			return (int)$this->a_declaration['max_length'];
		}
		else {
			return 1000;
		}
	}
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [[
			'type' => \Reef\Storage\Storage::TYPE_TEXT,
			'limit' => $this->getMaxLength(),
		]];
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
		$a_vars['value'] = (string)$Value->toTemplateVar();
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function view_submission($Value, $a_options = []) : array {
		$a_vars = parent::view_submission($Value, $a_options);
		$a_vars['value'] = (string)$Value->toTemplateVar();
		$a_vars['hasValue'] = $a_vars['value'] !== '' && $a_vars['value'] !== null;
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function updateDataLoss($OldField) {
		if($OldField->getMaxLength() > $this->getMaxLength()) {
			return Updater::DATALOSS_POTENTIAL;
		}
		
		return Updater::DATALOSS_NO;
	}
	
	/**
	 * @inherit
	 */
	public function beforeSchemaUpdate($a_data) {
		$NewField = $a_data['new_field'];
		$s_column = $a_data['old_columns'][0];
		
		if($this->getMaxLength() > $NewField->getMaxLength()) {
			switch($a_data['storageFactoryName']) {
				case \Reef\Storage\PDO_SQLite_StorageFactory::getName():
					$a_data['content_updater']('UPDATE '.$a_data['table'].' SET '.$s_column.' = SUBSTR('.$s_column.', 1, '.$NewField->getMaxLength().') WHERE LENGTH('.$s_column.') > '.$NewField->getMaxLength().' ');
				break;
				case \Reef\Storage\PDO_MySQL_StorageFactory::getName():
					$a_data['content_updater']('UPDATE '.$a_data['table'].' SET '.$s_column.' = SUBSTR('.$s_column.', 1, '.$NewField->getMaxLength().') WHERE CHAR_LENGTH('.$s_column.') > '.$NewField->getMaxLength().' ');
				break;
			}
		}
	}
}
