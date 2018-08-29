<?php

namespace Reef\Components\Upload;

use Reef\Components\Field;
use Reef\Updater;
use \Reef\Components\Traits\Required\RequiredFieldInterface;
use \Reef\Components\Traits\Required\RequiredFieldTrait;

class UploadField extends Field implements RequiredFieldInterface {
	
	const MAX_FILES = 100;
	
	use RequiredFieldTrait;
	
	/**
	 * @inherit
	 */
	public function validateDeclaration(array &$a_errors = null) : bool {
		$b_valid = parent::validateDeclaration($a_errors);
		
		$b_valid = $this->validateDeclaration_required($a_errors) && $b_valid;
		
		return $b_valid;
	}
	
	public function multipleFilesAllowed() {
		return $this->a_declaration['multiple'] ?? false;
	}
	
	public function getMaxFiles() {
		if(!$this->multipleFilesAllowed()) {
			return 1;
		}
		
		return min($this->a_declaration['max_files'] ?? static::MAX_FILES, ini_get('max_file_uploads'), static::MAX_FILES);
	}
	
	/**
	 * @inherit
	 */
	public function getFlatStructure() : array {
		return [[
			'type' => \Reef\Storage\Storage::TYPE_TEXT,
			'limit' => 33 * static::MAX_FILES, // 32 chars per file plus 1 delimiter
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
		$a_vars['files_data'] = json_encode($Value->toTemplateVar());
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function view_submission($Value, $a_options = []) : array {
		$a_vars = parent::view_submission($Value, $a_options);
		
		$i_byteBase = $this->getForm()->getReef()->getOption('byte_base');
		
		$a_filesData = $Value->toTemplateVar();
		foreach($a_filesData as &$a_fileData) {
			$a_fileData['request_hash'] = 'form:'.$this->getForm()->getUUID().':submission:'.$Value->getSubmission()->getUUID().':field:'.$this->a_declaration['name'].':download:'.$a_fileData['uuid'];
			$a_fileData['size'] = \Reef\bytes_format($a_fileData['size'], $i_byteBase);
		}
		
		$a_vars['files_data'] = $a_filesData;
		return $a_vars;
	}
	
	/**
	 * @inherit
	 */
	public function updateDataLoss($OldField) {
		return Updater::DATALOSS_NO;
	}
}
