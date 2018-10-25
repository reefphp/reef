<?php

namespace Reef\Storage;

use \PDO;
use \Reef\Exception\InvalidArgumentException;

/**
 * Factory for PDO_MySQL_Storage objects
 */
class PDO_MySQL_StorageFactory extends PDOStorageFactory {
	
	/**
	 * @inherit
	 */
	public function __construct(PDO $PDO) {
		parent::__construct($PDO);
		
		if($PDO->query("SELECT CHARSET('')")->fetchColumn() != 'utf8mb4') {
			// @codeCoverageIgnoreStart
			throw new InvalidArgumentException("Please use utf8mb4 as MySQL charset, e.g. by setting `;charset=utf8mb4` in the PDO constructor or by using `SET NAMES utf8mb4;`.");
			// @codeCoverageIgnoreEnd
		}
	}
	
	/**
	 * @inherit
	 */
	public static function getName() : string {
		return 'pdo_mysql';
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
