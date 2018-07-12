<?php

namespace Reef\Storage;

class DataStore {
	
	private $StorageFactory;
	
	private $FormStorage;
	private $a_submissionStorages = [];
	
	private $s_prefix;
	
	public function __construct(StorageFactory $StorageFactory) {
		$this->StorageFactory = $StorageFactory;
	}
	
	public function getFormStorage() : Storage {
		if(empty($this->FormStorage)) {
			$this->FormStorage = $this->StorageFactory->getStorage($this->s_prefix.'forms');
		}
		
		if(count($this->FormStorage->getColumns()) == 0) {
			$this->FormStorage->addColumns([
				'declaration' => [
					'type' => Storage::TYPE_TEXT,
					'limit' => 4194303,
				],
			]);
		}
		
		return $this->FormStorage;
	}
	
	public function hasSubmissionStorage($s_storageName) : bool {
		return $this->StorageFactory->hasStorage($this->s_prefix.'form_'.$s_storageName);
	}
	
	public function getSubmissionStorage(\Reef\StoredForm $Form) : ?Storage {
		$s_storageName = $Form->getStorageName();
		
		if($s_storageName === null) {
			return null;
		}
		
		if(empty($this->a_submissionStorages[$s_storageName])) {
			$this->a_submissionStorages[$s_storageName] = $this->StorageFactory->getStorage($this->s_prefix.'form_'.$s_storageName);
		}
		
		return $this->a_submissionStorages[$s_storageName];
	}
	
	public function deleteSubmissionStorage(\Reef\StoredForm $Form) {
		$this->getSubmissionStorage($Form)->deleteStorage();
		unset($this->a_submissionStorages[$Form->getStorageName()]);
	}
	
	public function deleteSubmissionStorageIfExists(\Reef\StoredForm $Form) {
		if($this->hasSubmissionStorage($Form->getStorageName())) {
			$this->deleteSubmissionStorage($Form);
		}
	}
	
	public function changeSubmissionStorageName(\Reef\StoredForm $Form, $s_newStorageName) {
		if(!$this->hasSubmissionStorage($Form->getStorageName())) {
			throw new \Exception("Storage not found for renaming");
		}
		
		$this->getSubmissionStorage($Form)->renameStorage(
			$this->s_prefix.'form_'.$s_newStorageName
		);
		
		unset($this->a_submissionStorages[$Form->getStorageName()]);
	}
	
}
