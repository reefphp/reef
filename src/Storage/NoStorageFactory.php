<?php

namespace Reef\Storage;

class NoStorageFactory implements StorageFactory {
	
	public function __construct() {
	}
	
	public function getStorage(string $s_storageName) {
		return new NoStorage();
	}
	
	public function hasStorage(string $s_storageName) {
		return false;
	}
	
}