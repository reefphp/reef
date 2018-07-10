<?php

namespace Reef\Storage;

interface StorageFactory {
	
	public function getStorage(string $s_storageName);
	
	public function hasStorage(string $s_storageName);
	
	public function renameStorage(string $s_oldStorageName, string $s_newStorageName);
	
}
