<?php

namespace Reef\Storage;

interface StorageFactory {
	
	/**
	 * Return the identifying name of this storage
	 */
	public static function getName() : string;
	
	public function getStorage(string $s_storageName);
	
	public function newStorage(string $s_storageName);
	
	public function hasStorage(string $s_storageName);
	
}
