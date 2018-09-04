<?php

namespace Reef\Storage;

/**
 * Factory for PDO_SQLite_Storage objects
 */
class PDO_SQLite_StorageFactory extends PDOStorageFactory {
	
	/**
	 * @inherit
	 */
	public static function getName() : string {
		return 'sqlite';
	}
	
	/**
	 * @inherit
	 */
	protected function getStorageClass() {
		return '\\Reef\\Storage\\PDO_SQLite_Storage';
	}
	
	/**
	 * @inherit
	 */
	protected function getPDODriverName() {
		return 'sqlite';
	}
	
}
