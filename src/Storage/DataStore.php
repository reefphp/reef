<?php

namespace Reef\Storage;

use \Reef\Form\StoredForm;
use \Reef\Exception\StorageException;

class DataStore {
	
	private $StorageFactory;
	
	private $FormStorage;
	private $a_submissionStorages = [];
	
	private $s_prefix;
	
	public function __construct(StorageFactory $StorageFactory, $a_options) {
		$this->StorageFactory = $StorageFactory;
		$this->s_prefix = $a_options['prefix'];
	}
	
	public function getFormStorage() : Storage {
		if(empty($this->FormStorage)) {
			if($this->StorageFactory->hasStorage($this->s_prefix.'_forms')) {
				$this->FormStorage = $this->StorageFactory->getStorage($this->s_prefix.'_forms');
			}
			else {
				$this->FormStorage = $this->StorageFactory->newStorage($this->s_prefix.'_forms');
				
				$this->FormStorage->addColumns([
					'definition' => [
						'type' => Storage::TYPE_TEXT,
						'limit' => 4194303,
					],
				]);
			}
		}
		
		return $this->FormStorage;
	}
	
	public function hasSubmissionStorage($s_storageName) : bool {
		return $this->StorageFactory->hasStorage($this->s_prefix.$s_storageName);
	}
	
	public function createSubmissionStorage(StoredForm $Form) : Storage {
		$s_storageName = $Form->getStorageName();
		
		if($s_storageName === null) {
			throw new StorageException('Storage name is not set');
		}
		
		if(!empty($this->a_submissionStorages[$s_storageName]) || $this->StorageFactory->hasStorage($this->s_prefix.$s_storageName)) {
			throw new StorageException('Storage already exists');
		}
		
		$this->StorageFactory->newStorage($this->s_prefix.$s_storageName);
		
		return $this->getSubmissionStorage($Form);
	}
	
	public function getSubmissionStorage(StoredForm $Form) : Storage {
		$s_storageName = $Form->getStorageName();
		
		if($s_storageName === null) {
			throw new StorageException('Storage name is not set');
		}
		
		if(empty($this->a_submissionStorages[$s_storageName])) {
			$this->a_submissionStorages[$s_storageName] = $this->StorageFactory->getStorage($this->s_prefix.$s_storageName);
		}
		
		return $this->a_submissionStorages[$s_storageName];
	}
	
	public function deleteSubmissionStorage(StoredForm $Form) {
		$this->getSubmissionStorage($Form)->deleteStorage();
		unset($this->a_submissionStorages[$Form->getStorageName()]);
	}
	
	public function deleteSubmissionStorageIfExists(StoredForm $Form) {
		if($this->hasSubmissionStorage($Form->getStorageName())) {
			$this->deleteSubmissionStorage($Form);
		}
	}
	
	public function changeSubmissionStorageName(StoredForm $Form, $s_newStorageName) {
		if(!$this->hasSubmissionStorage($Form->getStorageName())) {
			throw new StorageException("Storage not found for renaming");
		}
		
		$this->getSubmissionStorage($Form)->renameStorage(
			$this->s_prefix.$s_newStorageName
		);
		
		unset($this->a_submissionStorages[$Form->getStorageName()]);
	}
	
	public function ensureTransaction($fn_callback) {
		return $this->StorageFactory->ensureTransaction($fn_callback);
	}
	
}
