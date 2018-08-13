<?php

namespace Reef\Storage;

use \PDO;
use \Reef\Exception\StorageException;
use \Reef\Exception\RuntimeException;
use \Reef\Exception\InvalidArgumentException;

abstract class PDOStorageFactory implements StorageFactory {
	private $PDO;
	private $a_savepoints = [];
	private $i_savepointCounter = 0;
	
	static public function createFactory(PDO $PDO) : PDOStorageFactory {
		switch($PDO->getAttribute(PDO::ATTR_DRIVER_NAME)) {
			case 'sqlite':
				return new PDO_SQLite_StorageFactory($PDO);
			case 'mysql':
				return new PDO_MySQL_StorageFactory($PDO);
			// @codeCoverageIgnoreStart
			default:
				throw new InvalidArgumentException("Unsupported PDO driver ".$PDO->getAttribute(PDO::ATTR_DRIVER_NAME).".");
			// @codeCoverageIgnoreEnd
		}
	}
	
	public function __construct(PDO $PDO) {
		$this->PDO = $PDO;
		$this->PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		if($this->PDO->getAttribute(PDO::ATTR_DRIVER_NAME) != $this->getPDODriverName()) {
			// @codeCoverageIgnoreStart
			throw new InvalidArgumentException("PDO storage ".$this->getPDODriverName()." does not support PDO driver ".$this->PDO->getAttribute(PDO::ATTR_DRIVER_NAME)." ");
			// @codeCoverageIgnoreEnd
		}
	}
	
	abstract protected function getStorageClass();
	abstract protected function getPDODriverName();
	
	public function getStorage(string $s_tableName) {
		$s_storageClass = $this->getStorageClass();
		
		if(!$this->hasStorage($s_tableName)) {
			return $s_storageClass::createStorage($this, $this->PDO, $s_tableName);
		}
		
		return new $s_storageClass($this, $this->PDO, $s_tableName);
	}
	
	public function hasStorage(string $s_tableName) {
		$s_storageClass = $this->getStorageClass();
		
		return $s_storageClass::table_exists($this, $this->PDO, $s_tableName);
	}
	
	public function inTransaction() {
		return (count($this->a_savepoints) > 0);
	}
	
	public function ensureTransaction($fn_callback) {
		
		$s_storageClass = $this->getStorageClass();
		if(count($this->a_savepoints) == 0) {
			$s_storageClass::startTransaction($this->PDO);
		}
		
		$s_savepoint = 'savepoint_'.($this->i_savepointCounter++);
		$s_storageClass::newSavepoint($this->PDO, $s_savepoint);
		$this->a_savepoints[] = $s_savepoint;
		
		try {
			$m_return = $fn_callback();
		}
		catch(StorageException $e) {
			if(end($this->a_savepoints) != $s_savepoint) {
				// @codeCoverageIgnoreStart
				$s_storageClass::rollbackTransaction($this->PDO);
				throw new RuntimeException("Corrupt last savepoint", null, $e);
				// @codeCoverageIgnoreEnd
			}
			
			$s_storageClass::rollbackToSavepoint($this->PDO, $s_savepoint);
			throw $e;
		}
		finally {
			array_pop($this->a_savepoints);
		}
		
		if(count($this->a_savepoints) == 0) {
			$s_storageClass::commitTransaction($this->PDO);
		}
		
		return $m_return;
	}
	
}
