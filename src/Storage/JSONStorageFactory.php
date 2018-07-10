<?php

namespace Reef\Storage;

class JSONStorageFactory implements StorageFactory {
	private $s_path;
	
	public function __construct(string $s_path) {
		$this->s_path = $s_path;
	}
	
	public function getStorage(string $s_storageName) {
		$s_storagePath = $this->getStoragePath($s_storageName);
		
		if(!is_dir($s_storagePath)) {
			mkdir($s_storagePath, 0755, true);
		}
		
		return new JSONStorage($s_storagePath);
	}
	
	public function hasStorage(string $s_storageName) {
		return is_dir($this->getStoragePath($s_storageName));
	}
	
	public function renameStorage(string $s_oldStorageName, string $s_newStorageName) {
		return rename($this->getStoragePath($s_oldStorageName), $this->getStoragePath($s_newStorageName));
	}
	
	private function getStoragePath($s_storageName) {
		return rtrim($this->s_path, '/').'/'.$s_storageName.'/';
	}
	
}
