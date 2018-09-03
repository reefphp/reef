<?php

namespace tests\Session;

use PHPUnit\Framework\TestCase;
use \Reef\Session\SessionInterface;
use \Reef\Exception\BadMethodCallException;

class NoSessionTest extends TestCase {
	
	private static $SessionObject;
	
	public function testCanBeCreated(): void {
		self::$SessionObject = new \Reef\Session\NoSession();
		
		$this->assertInstanceOf(SessionInterface::class, self::$SessionObject);
	}
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testSet(): void {
		$this->expectException(BadMethodCallException::class);
		
		self::$SessionObject->set('key', 'value');
	}
	
	/**
	 * @depends testSet
	 */
	public function testHas(): void {
		$this->expectException(BadMethodCallException::class);
		
		self::$SessionObject->has('key');
	}
	
	/**
	 * @depends testHas
	 */
	public function testGet(): void {
		$this->expectException(BadMethodCallException::class);
		
		self::$SessionObject->get('key');
	}
	
	/**
	 * @depends testGet
	 */
	public function testRemove(): void {
		$this->expectException(BadMethodCallException::class);
		
		self::$SessionObject->remove('key');
	}
	
}
