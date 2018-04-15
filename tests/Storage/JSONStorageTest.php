<?php

namespace tests\Storage;

use PHPUnit\Framework\TestCase;
use Reef\Exception\IOException;
use Reef\Storage\JSONStorage;

final class JSONStorageTest extends TestCase {
	
	const STORAGE_DIR = 'var/tmp/test/json_storage';
	
	private static $JSONStorage;
	
	public static function setUpBeforeClass() {
		if(!is_dir(self::STORAGE_DIR)) {
			mkdir(self::STORAGE_DIR, 0777);
		}
	}
	
	public function testCanBeCreatedFromValidPath(): void {
		$this->assertInstanceOf(
			JSONStorage::class,
			self::$JSONStorage = new JSONStorage(self::STORAGE_DIR)
		);
	}
	
	public function testCannotBeCreatedFromInvalidPath(): void {
		$this->expectException(IOException::class);
		
		new JSONStorage('invalid');
	}
	
	/**
	 * @depends testCanBeCreatedFromValidPath
	 */
	public function testCanInsertData(): int {
		$a_data = [2 => 'value'];
		
		$i_entryId = self::$JSONStorage->insert($a_data);
		$this->assertInternalType('int', $i_entryId);
		
		$this->assertTrue(self::$JSONStorage->exists($i_entryId));
		
		$a_data2 = self::$JSONStorage->get($i_entryId);
		$this->assertInternalType('array', $a_data2);
		
		$this->assertSame($a_data, $a_data2);
		
		return $i_entryId;
	}
	
	/**
	 * @depends testCanInsertData
	 */
	public function testCanUpdateData(int $i_entryId): int {
		$a_data = [3 => 'test'];
		
		self::$JSONStorage->update($i_entryId, $a_data);
		
		$a_data2 = self::$JSONStorage->get($i_entryId);
		$this->assertInternalType('array', $a_data2);
		
		$this->assertSame($a_data, $a_data2);
		
		return $i_entryId;
	}
	
	/**
	 * @depends testCanUpdateData
	 */
	public function testCanDeleteData(int $i_entryId): void {
		self::$JSONStorage->delete($i_entryId);
		
		$this->assertFalse(self::$JSONStorage->exists($i_entryId));
	}
}
