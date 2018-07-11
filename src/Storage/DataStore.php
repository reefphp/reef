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
	
	public function getSubmissionStorage(\Reef\Form $Form) : ?Storage {
		$s_storageName = $Form->getStorageName();
		
		if($s_storageName === null) {
			return null;
		}
		
		if(empty($this->a_submissionStorages[$s_storageName])) {
			$this->a_submissionStorages[$s_storageName] = $this->StorageFactory->getStorage($this->s_prefix.'form_'.$s_storageName);
		}
		
		return $this->a_submissionStorages[$s_storageName];
	}
	
	public function deleteSubmissionStorage(\Reef\Form $Form) {
		$this->getSubmissionStorage($Form)->deleteStorage();
		unset($this->a_submissionStorages[$Form->getStorageName()]);
	}
	
	public function deleteSubmissionStorageIfExists(\Reef\Form $Form) {
		if($this->hasSubmissionStorage($Form->getStorageName())) {
			$this->deleteSubmissionStorage($Form);
		}
	}
	
	public function changeSubmissionStorageName(\Reef\Form $Form, $s_newStorageName) {
		if(!$this->hasSubmissionStorage($Form->getStorageName())) {
			return;
		}
		
		$this->StorageFactory->getSubmissionStorage($this->s_prefix.'form_'.$Form->getStorageName())->renameStorage(
			$this->s_prefix.'form_'.$s_newStorageName
		);
		
		unset($this->a_submissionStorages[$Form->getStorageName()]);
	}
	
}
