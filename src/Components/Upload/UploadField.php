<?php

namespace Reef\Components\Upload;

use Reef\Components\Field;
use Reef\Updater;
use \Reef\Components\Traits\Required\RequiredFieldInterface;
use \Reef\Components\Traits\Required\RequiredFieldTrait;

class UploadField extends Field implements RequiredFieldInterface {
	
	const MAX_FILES = 100;
	
	use RequiredFieldTrait;
	
	private $a_allowedExtensions;
	
	/**
	 * @inherit
	 */
	public function validateDeclaration(array &$a_errors = null) : bool {
		$b_valid = parent::validateDeclaration($a_errors);
		
		$b_valid = $this->validateDeclaration_required($a_errors) && $b_valid;
		
		return $b_valid;
	}
	
	public function multipleFilesAllowed() : bool {
		return $this->a_declaration['multiple'] ?? false;
	}
	
	public function getMaxFiles() : int {
		if(!$this->multipleFilesAllowed()) {
			return 1;
		}
		
		return min($this->a_declaration['max_files'] ?? static::MAX_FILES, ini_get('max_file_uploads'), static::MAX_FILES);
	}
	
	public function getAllowedExtensions() : array {
		if($this->a_allowedExtensions === null) {
			$this->a_allowedExtensions = array_intersect(
				isset($this->a_declaration['types']) ? array_keys(array_filter($this->a_declaration['types'])) : $this->getComponent()->getDefaultTypes(),
				$this->getForm()->getReef()->getDataStore()->getFilesystem()->getAllowedExtensions()
			);
		}
		return $this->a_allowedExtensions;
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
		$a_vars['max_files'] = $this->getMaxFiles();
		$a_vars['max_file_size'] = $this->getForm()->getReef()->getDataStore()->getFilesystem()->getMaxUploadSize();
		$a_vars['accepted_types'] = implode(',', $this->getAllowedExtensions());
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
	
	/**
	 * @inherit
	 */
	protected function getLanguageReplacements() : array {
		$Filesystem = $this->getForm()->getReef()->getDataStore()->getFilesystem();
		$i_byteBase = $this->getForm()->getReef()->getOption('byte_base');
		
		return [
			'max_files' => $this->getMaxFiles(),
			'max_size' => \Reef\bytes_format($Filesystem->getMaxUploadSize(), $i_byteBase),
			'accepted_types' => '.' . implode(', .', $this->getAllowedExtensions()),
		];
	}
}
