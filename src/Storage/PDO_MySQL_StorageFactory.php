<?php

namespace Reef\Storage;

class PDO_MySQL_StorageFactory extends PDOStorageFactory {
	
	protected function getStorageClass() {
		return '\Reef\Storage\PDO_MySQL_Storage';
	}
	
	protected function getPDODriverName() {
		return 'mysql';
	}
	
}
