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
	
}
