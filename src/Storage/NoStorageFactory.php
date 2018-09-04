<?php

namespace Reef\Storage;

/**
 * Factory for NoStorage objects
 */
class NoStorageFactory implements StorageFactory {
	
	/**
	 * @inherit
	 */
	public static function getName() : string {
		return 'nostorage';
	}
	
	/**
	 * Constructor
	 */
	public function __construct() {
	}
	
	/**
	 * @inherit
	 */
	public function getStorage(string $s_storageName) {
		return new NoStorage();
	}
	
	/**
	 * @inherit
	 */
	public function newStorage(string $s_storageName) {
		return new NoStorage();
	}
	
	/**
	 * @inherit
	 */
	public function hasStorage(string $s_storageName) {
		return false;
	}
	
}
