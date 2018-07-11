<?php

namespace Reef\Storage;

use \PDO;

class PDOStorageFactory implements StorageFactory {
	private $PDO;
	
	public function __construct(PDO $PDO) {
		$this->PDO = $PDO;
		$this->PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	public function getStorage(string $s_tableName) {
		$s_storageClass = $this->getStorageClass();
		
		if(!$this->hasStorage($s_tableName)) {
			return $s_storageClass::createStorage($this->PDO, $s_tableName);
		}
		
		return new $s_storageClass($this->PDO, $s_tableName);
	}
	
	public function hasStorage(string $s_tableName) {
		$s_storageClass = $this->getStorageClass();
		
		return $s_storageClass::table_exists($this->PDO, $s_tableName);
	}
	
	public function renameStorage(string $s_oldTableName, string $s_newTableName) {
		throw new \Exception("Deprecated");
	}
	
	private function getStorageClass() {
		switch($this->PDO->getAttribute(PDO::ATTR_DRIVER_NAME)) {
			case 'sqlite':
				return '\Reef\Storage\PDO_SQLite_Storage';
			case 'mysql':
				return '\Reef\Storage\PDO_MySQL_Storage';
			default:
				throw new \Exception("Unsupported PDO driver ".$this->PDO->getAttribute(PDO::ATTR_DRIVER_NAME).".");
		}
	}
	
}
