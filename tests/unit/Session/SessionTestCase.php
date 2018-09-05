<?php

namespace ReefTests\unit\Session;

use PHPUnit\Framework\TestCase;
use \Reef\Session\SessionInterface;

abstract class SessionTestCase extends TestCase {
	
	private static $SessionObject;
	
	abstract public function createSessionObject(): SessionInterface;
	
	public function testCanBeCreated(): void {
		self::$SessionObject = $this->createSessionObject();
		
		$this->assertInstanceOf(SessionInterface::class, self::$SessionObject);
	}
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testSet(): void {
		
		self::$SessionObject->set('key1', 'value1');
		$this->assertSame('value1', self::$SessionObject->get('key1'));
		
		self::$SessionObject->set('key1', 'value1a');
		$this->assertSame('value1a', self::$SessionObject->get('key1'));
		
	}
	
	/**
	 * @depends testSet
	 */
	public function testHas(): void {
		$this->assertTrue(self::$SessionObject->has('key1'));
		$this->assertFalse(self::$SessionObject->has('key2'));
	}
	
	/**
	 * @depends testHas
	 */
	public function testGet(): void {
		$this->assertSame(null, self::$SessionObject->get('non-existent'));
		$this->assertSame('asdf', self::$SessionObject->get('non-existent', 'asdf'));
		
		self::$SessionObject->set('existent', 'some-value');
		
		$this->assertSame('some-value', self::$SessionObject->get('existent'));
		$this->assertSame('some-value', self::$SessionObject->get('existent', 'asdf'));
	}
	
	/**
	 * @depends testGet
	 */
	public function testRemove(): void {
		$this->assertSame('some-value', self::$SessionObject->remove('existent'));
		$this->assertSame(null, self::$SessionObject->get('existent'));
		
		$this->assertSame(null, self::$SessionObject->remove('existent'));
	}
	
}
