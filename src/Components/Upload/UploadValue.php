<?php

namespace Reef\Components\Upload;

use Reef\Components\FieldValue;
use \Reef\Exception\FilesystemException;
use \Reef\Components\Traits\Required\RequiredFieldValueInterface;
use \Reef\Components\Traits\Required\RequiredFieldValueTrait;

class UploadValue extends FieldValue implements RequiredFieldValueInterface {
	
	use RequiredFieldValueTrait;
	
	protected $a_uuids;
	protected $a_uuidsDel;
	
	/**
	 * Get all files of this field
	 * @param bool $b_includeDeleted Whether to include recently deleted files. Optional, defaults to false
	 * @return File[]
	 */
	public function getFiles(bool $b_includeDeleted = false) {
		$Filesystem = $this->getField()->getForm()->getReef()->getDataStore()->getFilesystem();
		
		$a_uuids = $this->a_uuids??[];
		if($b_includeDeleted) {
			$a_uuids = array_merge($a_uuids, $this->a_uuidsDel??[]);
		}
		
		$a_files = [];
		foreach($a_uuids as $s_uuid) {
			try {
				$a_files[] = $Filesystem->getFile($s_uuid, $this);
			}
			catch(FilesystemException $e) {
				$a_files[] = $Filesystem->getFile($s_uuid, 'upload');
			}
		}
		
		return $a_files;
	}
	
	/**
	 * Delete a file
	 * @param \Reef\Filesystem\File $File The file to delete
	 * @return File[]
	 */
	public function deleteFile(\Reef\Filesystem\File $File) {
		$i_key = array_search($File->getUUID(), $this->a_uuids);
		if($i_key === false) {
			throw new \Reef\Exception\DomainException('File "'.$File->getUUID().'" does not belong to this field');
		}
		
		$Filesystem = $this->getField()->getForm()->getReef()->getDataStore()->getFilesystem();
		$Filesystem->deleteFile($File);
		unset($this->a_uuids[$i_key]);
	}
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		
		if(!$this->validate_required(empty($this->a_uuids), $this->a_errors)) {
			return false;
		}
		
		if(count($this->a_uuids) > $this->Field->getMaxFiles()) {
			$this->a_errors[] = $this->Field->trans('error_too_many_files');
			return false;
		}
		
		$a_allowedExtensions = $this->Field->getAllowedExtensions();
		foreach($this->getFiles() as $File) {
			if(!in_array($File->getExtension(), $a_allowedExtensions)) {
				$this->a_errors[] = $this->Field->trans('error_file_type') . ' ('.$File->getExtension().')';
				return false;
			}
		}
		
		if(!empty($this->a_uuidsMissing)) {
			$this->a_errors[] = 'Files not found: '.implode(', ', $this->a_uuidsMissing);
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->a_uuids = [];
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return empty($this->a_uuids);
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($a_uuids) {
		$Filesystem = $this->getField()->getForm()->getReef()->getDataStore()->getFilesystem();
		$Session = $this->getField()->getForm()->getReef()->getSession();
		$a_uploadedFiles = $Session->get($this->getField()->getComponent(), 'uploaded_files', []);
		
		$a_oldUUIDS = array_flip($this->a_uuids??[]);
		
		$this->a_uuidsDel = $this->a_uuids = $this->a_uuidsMissing = [];
		
		foreach($a_uuids??[] as $s_uuid) {
			if(empty($s_uuid)) {
				continue;
			}
			
			$b_delete = false;
			if(substr($s_uuid, 0, 1) == 'x') {
				$b_delete = true;
				$s_uuid = substr($s_uuid, 1);
			}
			
			if(isset($a_oldUUIDS[$s_uuid])) {
				// Already was present
				unset($a_oldUUIDS[$s_uuid]);
				try {
					$File = $Filesystem->getFile($s_uuid, $this);
				}
				catch(FilesystemException $e) {
					$this->a_uuidsMissing[] = $s_uuid;
					continue;
				}
				
				if($b_delete) {
					$Filesystem->deleteFile($File);
				}
			}
			else {
				// Was not already present, so it has been newly uploaded
				try {
					$File = $Filesystem->getFile($s_uuid, 'upload');
				}
				catch(FilesystemException $e) {
					$this->a_uuidsMissing[] = $s_uuid;
					continue;
				}
				
				if(!in_array($s_uuid, $a_uploadedFiles)) {
					$this->a_uuidsMissing[] = $s_uuid;
					continue;
				}
				
				if($b_delete) {
					$Filesystem->deleteFile($File);
				}
				else {
					$Filesystem->changeFileContext($File, $this);
				}
			}
			
			if($b_delete) {
				$this->a_uuidsDel[] = $File->getUUID();
			}
			else {
				$this->a_uuids[] = $File->getUUID();
			}
		}
		
		foreach($a_oldUUIDS as $s_uuid => $tmp) {
			$File = $Filesystem->getFile($s_uuid, $this);
			$Filesystem->deleteFile($File);
			$this->a_uuidsDel[] = $File->getUUID();
		}
		
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($a_uuids) {
		$this->a_uuids = $a_uuids;
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
			implode(',', $this->a_uuids),
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
		$s_uuids = $a_flat[0]??'';
		$this->a_uuids = empty($s_uuids) ? [] : explode(',', $s_uuids);
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toStructured() {
		return $this->a_uuids;
	}
	
	/**
	 * @inherit
	 */
	public function toOverviewColumns() : array {
		return [
			count($this->a_uuids) . ' files',
		];
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		$a_filesVar = [];
		foreach($this->getFiles(true) as $File) {
			$a_filesVar[] = [
				'name' => $File->getFilename(),
				'uuid' => $File->getUUID(),
				'size' => $File->getSize(),
				'deleted' => in_array($File->getUUID(), $this->a_uuidsDel??[]),
			];
		}
		return $a_filesVar;
	}
	
	/**
	 * @inherit
	 */
	public function validateConditionOperation(string $s_operator, $m_operand) {
		if(in_array($s_operator, ['is empty', 'is not empty'])) {
			if(!empty($m_operand)) {
				throw new \Reef\Exception\ConditionException('Empty does not take an operand');
			}
		}
	}
	
	/**
	 * @inherit
	 */
	public function evaluateConditionOperation(string $s_operator, $m_operand) : bool {
		switch($s_operator) {
			case 'is empty':
				return empty($this->a_uuids);
				
			case 'is not empty':
				return !empty($this->a_uuids);
		}
	}
	
	/**
	 * @inherit
	 */
	public function internalRequest(string $s_requestHash, array $a_options = []) {
		if(substr($s_requestHash, 0, 9) != 'download:') {
			throw new \Reef\Exception\InvalidArgumentException('Invalid request command');
		}
		
		$s_uuid = substr($s_requestHash, 9);
		
		try {
			$Filesystem = $this->getField()->getForm()->getReef()->getDataStore()->getFilesystem();
			$File = $Filesystem->getFile($s_uuid, $this);
			
			$File->stream();
		}
		catch(\Exception $e) {
			die($e->getMessage());
		}
	}
}
