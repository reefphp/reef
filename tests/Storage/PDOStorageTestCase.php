<?php

namespace tests\Storage;

use PHPUnit\Framework\TestCase;
use \PDOException;
use \Reef\Storage\Storage;

abstract class PDOStorageTestCase extends TestCase {
	
	protected static $PDO;
	protected static $PDO_Factory;
	protected static $Storage;
	protected static $s_storageName = 'test';
	
	public function testSanitization(): void {
		$this->assertSame('jfiei395Vxx__0fe0ee3', \Reef\Storage\PDOStorage::sanitizeName('!@#$%jfiei395()Vxx__0fe0-ee3#-'));
	}
	
	abstract public function testCanBeCreated(): void;
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testVerifyCreate(): void {
		$this->assertTrue(static::$PDO_Factory->hasStorage(static::$s_storageName));
		
		// Refetching the storage will create a new instance, hence we check Equals() instead of Same()
		$this->assertEquals(static::$Storage, static::$PDO_Factory->getStorage(static::$s_storageName));
		
		$this->assertSame(static::$s_storageName, static::$Storage->getTableName());
	}
	
	/**
	 * @depends testVerifyCreate
	 */
	public function testCanAddColumns(): void {
		static::$Storage->addColumns([
			'value' => [
				'type' => Storage::TYPE_TEXT,
				'limit' => 2000,
			],
			'number' => [
				'type' => Storage::TYPE_INTEGER,
			],
		]);
		
		$this->assertSame(static::$Storage->getColumns(), ['value', 'number']);
	}
	
	/**
	 * @depends testCanAddColumns
	 */
	public function testCanInsertData(): int {
		$a_data = [
			'value' => 'Test value',
			'number' => '42',
		];
		
		$this->assertSame(1, static::$Storage->next());
		
		$i_entryId = static::$Storage->insert($a_data);
		$this->assertInternalType('int', $i_entryId);
		
		$this->assertTrue(static::$Storage->exists($i_entryId));
		
		$this->assertSame(['1'], static::$Storage->list());
		$this->assertSame(2, static::$Storage->next());
		
		$a_data2 = static::$Storage->get($i_entryId);
		$this->assertInternalType('array', $a_data2);
		
		$this->assertSame($a_data, $a_data2);
		
		return $i_entryId;
	}
	
	/**
	 * @depends testCanAddColumns
	 */
	public function testRejectsInvalidData(): int {
		$this->expectException(\Reef\Exception\RuntimeException::class);
		
		$a_data = [
			'value' => 'Test value',
			'invalid_key' => '33',
		];
		
		static::$Storage->insert($a_data);
	}
	
	/**
	 * @depends testCanInsertData
	 */
	public function testCanUpdateData(int $i_entryId): int {
		$a_data = [
			'value' => 'Another value',
			'number' => '65',
		];
		
		static::$Storage->update($i_entryId, $a_data);
		
		$a_data2 = static::$Storage->get($i_entryId);
		$this->assertInternalType('array', $a_data2);
		
		$this->assertSame($a_data, $a_data2);
		
		return $i_entryId;
	}
	
	/**
	 * @depends testCanUpdateData
	 */
	public function testCanRenameColumn(int $i_entryId): int {
		$a_data = [
			'value2' => 'Another value',
			'number' => '65',
		];
		
		$a_structure = [
			'type' => Storage::TYPE_TEXT,
			'limit' => 2000,
		];
		
		static::$Storage->updateColumns(['value' => [
			'name' => 'value2',
			'structureFrom' => $a_structure,
			'structureTo' => $a_structure,
		]]);
		
		$a_data2 = static::$Storage->get($i_entryId);
		$this->assertInternalType('array', $a_data2);
		
		$this->assertSame($a_data, $a_data2);
		
		return $i_entryId;
	}
	
	/**
	 * @depends testCanRenameColumn
	 */
	public function testCanRemoveColumns(int $i_entryId): int {
		$a_data = [
			'value2' => 'Another value',
		];
		
		static::$Storage->removeColumns(['number']);
		
		$a_data2 = static::$Storage->get($i_entryId);
		$this->assertInternalType('array', $a_data2);
		
		$this->assertSame($a_data, $a_data2);
		
		return $i_entryId;
	}
	
	/**
	 * @depends testCanRenameColumn
	 */
	public function testCanDeleteData(int $i_entryId): void {
		static::$Storage->delete($i_entryId);
		
		$this->assertFalse(static::$Storage->exists($i_entryId));
	}
	
	/**
	 * @depends testCanDeleteData
	 */
	public function testCanDeleteStorage(): void {
		static::$Storage->deleteStorage();
		
		$this->assertFalse(static::$PDO_Factory->hasStorage(static::$s_storageName));
	}
}
