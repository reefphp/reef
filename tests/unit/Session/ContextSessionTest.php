<?php

namespace tests\Session;

use PHPUnit\Framework\TestCase;
use \Reef\Session\ContextSession;
use \Reef\Session\TmpSession;

class ContextSessionTest extends TestCase {
	
	private static $Reef;
	private static $Session;
	
	public function testCanBeCreated(): void {
		
		$Setup = new \Reef\ReefSetup(
			new \Reef\Storage\NoStorageFactory(),
			new \Reef\Layout\bootstrap4\bootstrap4(),
			new \Reef\Session\PhpSession()
		);
		
		static::$Reef = new \Reef\Reef(
			$Setup,
			[
			]
		);
		
		self::$Session = static::$Reef->getSession();
		
		$this->assertInstanceOf(ContextSession::class, self::$Session);
	}
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testSet(): void {
		$Form = self::$Reef->newTempForm();
		
		self::$Session->set($Form, 'key1', 'value1');
		$this->assertSame('value1', self::$Session->get($Form, 'key1'));
	}
	
	/**
	 * @depends testSet
	 */
	public function testHas(): void {
		$Form = self::$Reef->newTempForm();
		
		$this->assertTrue(self::$Session->has($Form, 'key1'));
	}
	
	/**
	 * @depends testHas
	 */
	public function testGet(): void {
		$Form = self::$Reef->newTempForm();
		
		$this->assertSame('value1', self::$Session->get($Form, 'key1', 'asdf'));
	}
	
	/**
	 * @depends testGet
	 */
	public function testRemove(): void {
		$Form = self::$Reef->newTempForm();
		
		$this->assertSame('value1', self::$Session->remove($Form, 'key1'));
		$this->assertSame('asdf', self::$Session->get($Form, 'key1', 'asdf'));
	}
	
}
