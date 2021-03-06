<?php

namespace ReefTests\unit\Storage;

use \PDOException;
use Reef\Storage\PDO_SQLite_Storage;

final class PDOSQLiteStorageTest extends PDOStorageTestCase {
	
	const STORAGE_DIR = TEST_TMP_DIR . '/sqlite_storage';
	
	public static function setUpBeforeClass() {
		if(!is_dir(static::STORAGE_DIR)) {
			mkdir(static::STORAGE_DIR, 0777, true);
		}
	}
	
	public function testCanBeCreated(): void {
		static::$PDO = new \PDO("sqlite:".static::STORAGE_DIR."/test.db");
		static::$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		static::$PDO_Factory = \Reef\Storage\PDOStorageFactory::createFactory(static::$PDO);
		
		$this->assertInstanceOf(
			PDO_SQLite_Storage::class,
			static::$Storage = static::$PDO_Factory->newStorage(static::$s_storageName)
		);
	}
	
	public static function tearDownAfterClass() {
		unlink(static::STORAGE_DIR."/test.db");
	}
}
