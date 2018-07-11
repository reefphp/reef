<?php

namespace tests\Storage;

require_once(__DIR__ . '/PDOStorageTestCase.php');

use \PDOException;
use Reef\Storage\PDO_SQLite_Storage;

final class PDOSQLiteStorageTest extends PDOStorageTestCase {
	
	const STORAGE_DIR = 'var/tmp/test/sqlite_storage';
	
	public static function setUpBeforeClass() {
		if(!is_dir(static::STORAGE_DIR)) {
			mkdir(static::STORAGE_DIR, 0777);
		}
	}
	
	public function testCanBeCreated(): void {
		static::$PDO = new \PDO("sqlite:".static::STORAGE_DIR."/test.db");
		static::$PDO_Factory = new \Reef\Storage\PDOStorageFactory(static::$PDO);
		
		$this->assertInstanceOf(
			PDO_SQLite_Storage::class,
			static::$Storage = static::$PDO_Factory->getStorage('test')
		);
		
		$this->assertTrue(static::$Storage::table_exists(static::$PDO, 'test'));
	}
	
	public static function tearDownAfterClass() {
		unlink(static::STORAGE_DIR."/test.db");
	}
}
