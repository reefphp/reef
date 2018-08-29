<?php

namespace Reef\Components\Upload;

use Reef\Components\FieldValue;
use \Reef\Components\Traits\Required\RequiredFieldValueInterface;
use \Reef\Components\Traits\Required\RequiredFieldValueTrait;

class UploadValue extends FieldValue implements RequiredFieldValueInterface {
	
	use RequiredFieldValueTrait;
	
	protected $a_uuids;
	
	public function getFiles() {
		$Filesystem = $this->getField()->getForm()->getReef()->getDataStore()->getFilesystem();
		
		$a_files = [];
		foreach($this->a_uuids??[] as $s_uuid) {
			$a_files[] = $Filesystem->getFile($s_uuid, $this);
		}
		
		return $a_files;
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
			$this->a_errors[] = $this->Field->trans('error_value_too_long');
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
		
		$a_oldUUIDS = array_flip($this->a_uuids??[]);
		
		$this->a_uuids = [];
		
		foreach($a_uuids??[] as $s_uuid) {
			if(empty($s_uuid)) {
				continue;
			}
			
			if(isset($a_oldUUIDS[$s_uuid])) {
				// Already was present
				unset($a_oldUUIDS[$s_uuid]);
				$File = $Filesystem->getFile($s_uuid, $this);
			}
			else {
				// Was not already present, so it has been newly uploaded
				$File = $Filesystem->getFile($s_uuid, 'upload');
				$Filesystem->changeFileContext($File, $this);
			}
			
			$this->a_uuids[] = $File->getUUID();
		}
		
		foreach($a_oldUUIDS as $s_uuid => $tmp) {
			$File = $Filesystem->getFile($s_uuid, $this);
			$Filesystem->deleteFile($File);
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
		$this->a_uuids = explode(',', $a_flat[0]??'');
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
			$this->a_uuids,
		];
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		$a_filesVar = [];
		foreach($this->getFiles() as $File) {
			$a_filesVar[] = [
				'name' => $File->getFilename(),
				'uuid' => $File->getUUID(),
				'size' => $File->getSize(),
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
		catch(Exception $e) {
			die($e->getMessage());
		}
	}
}
