<?php

namespace ReefTests\unit\Storage;

use \PDOException;
use Reef\Storage\PDO_MySQL_Storage;

final class PDOMySQLStorageTest extends PDOStorageTestCase {
	
	const DB_NAME = 'reef_test';
	const DB_USER = 'reef_test';
	const DB_PASS = 'reef_test';
	
	private static $PDOException = null;
	
	public static function setUpBeforeClass() {
		$DB_HOST = getenv('IN_GITLAB_CI') ? 'mysql' : '127.0.0.1';
		try {
			static::$PDO = new \PDO("mysql:dbname=".static::DB_NAME.";host=".$DB_HOST, static::DB_USER, static::DB_PASS);
		} catch (PDOException $e) {
			static::$PDO = null;
			static::$PDOException = $e;
		}
	}
	
	public function setUp() {
		if(empty(static::$PDO)) {
			$this->markTestSkipped(
				'Could not connect to MySQL database ('.static::$PDOException->getMessage().')'
			);
		}
	}
	
	public function testCanBeCreated(): void {
		static::$PDO_Factory = \Reef\Storage\PDOStorageFactory::createFactory(static::$PDO);
		
		$this->assertInstanceOf(
			PDO_MySQL_Storage::class,
			static::$Storage = static::$PDO_Factory->newStorage(static::$s_storageName)
		);
	}
	
	public static function tearDownAfterClass() {
		if(empty(static::$PDO)) {
			return;
		}
		
		// Make sure the test table is removed, even if some test has failed
		$sth = static::$PDO->prepare("
			DROP TABLE IF EXISTS ".static::$s_storageName.";
		");
		$sth->execute();
	}
}
