<?php

namespace tests\Storage;

require_once(__DIR__ . '/PDOStorageTestCase.php');

use \PDOException;
use Reef\Storage\PDO_MySQL_Storage;

final class PDOMySQLStorageTest extends PDOStorageTestCase {
	
	const DB_NAME = 'reef_test';
	const DB_HOST = '127.0.0.1';
	const DB_USER = 'reef_test';
	const DB_PASS = 'reef_test';
	
	public static function setUpBeforeClass() {
		try {
			static::$PDO = new \PDO("mysql:dbname=".static::DB_NAME.";host=".static::DB_HOST, static::DB_USER, static::DB_PASS);
		} catch (PDOException $e) {
			$this->markTestSkipped(
				'Cannot connect to MySQL database ('.$e->getMessage().')'
			);
		}
	}
	
	public function testCanBeCreated(): void {
		static::$PDO_Factory = new \Reef\Storage\PDOStorageFactory(static::$PDO);
		
		$this->assertInstanceOf(
			PDO_MySQL_Storage::class,
			static::$Storage = static::$PDO_Factory->getStorage('test')
		);
		
		$this->assertTrue(static::$Storage::table_exists(static::$PDO, 'test'));
	}
	
	public static function tearDownAfterClass() {
		// Make sure the test table is removed, even if some test has failed
		$sth = static::$PDO->prepare("
			DROP TABLE IF EXISTS test;
		");
		$sth->execute();
	}
}
