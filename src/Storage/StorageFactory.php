<?php

namespace Reef\Storage;

/**
 * Interface for storageFactory classes
 */
interface StorageFactory {
	
	/**
	 * Return the identifying name of this storage
	 */
	public static function getName() : string;
	
	/**
	 * Retrieve the requested storage
	 * @param string $s_storageName The storage name to retrieve
	 * @return Storage
	 */
	public function getStorage(string $s_storageName);
	
	/**
	 * Create new storage
	 * @param string $s_storageName The storage name to create
	 * @return Storage
	 */
	public function newStorage(string $s_storageName);
	
	/**
	 * Determine whether the given storage exists
	 * @param string $s_storageName The storage name to check
	 * @return bool
	 */
	public function hasStorage(string $s_storageName);
	
}
