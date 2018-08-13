<?php

namespace Reef\Storage;

class PDO_SQLite_StorageFactory extends PDOStorageFactory {
	
	protected function getStorageClass() {
		return '\Reef\Storage\PDO_SQLite_Storage';
	}
	
	protected function getPDODriverName() {
		return 'sqlite';
	}
	
}
