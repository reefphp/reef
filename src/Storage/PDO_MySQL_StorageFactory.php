<?php

namespace Reef\Storage;

/**
 * Factory for PDO_MySQL_Storage objects
 */
class PDO_MySQL_StorageFactory extends PDOStorageFactory {
	
	/**
	 * @inherit
	 */
	public static function getName() : string {
		return 'mysql';
	}
	
	/**
	 * @inherit
	 */
	protected function getStorageClass() {
		return '\\Reef\\Storage\\PDO_MySQL_Storage';
	}
	
	/**
	 * @inherit
	 */
	protected function getPDODriverName() {
		return 'mysql';
	}
	
}
