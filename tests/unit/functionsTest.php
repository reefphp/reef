<?php

namespace tests\Form;

use PHPUnit\Framework\TestCase;

class_exists('\\Reef\\Reef'); // Make sure functions are loaded

final class functionsTest extends TestCase {
	
	const STORAGE_DIR = 'var/tmp/test/functions';
	
	public static function setUpBeforeClass() {
		if(!is_dir(static::STORAGE_DIR)) {
			mkdir(static::STORAGE_DIR, 0777);
		}
	}
	
	public static function tearDownAfterClass() {
		\Reef\rmTree(static::STORAGE_DIR, true, true);
	}
	
	public function test_array_subset(): void {
		$this->assertSame(
			['a' => 5, 'c' => 3, 'k' => 3],
			\Reef\array_subset(
				['a' => 5, 'b' => 4, 'c' => 3, 'd' => 'asdf', 'k' => 3],
				['c', 'a', 'e', 'k']
			)
		);
		
		$this->assertSame([], \Reef\array_subset([], []));
	}
	
	public function test_unique_id(): void {
		$this->assertNotEquals(\Reef\unique_id(), \Reef\unique_id());
	}
	
	public function test_array_first_key(): void {
		$this->assertSame('a', \Reef\array_first_key(['a' => 4, 'b' => 5]));
		$this->assertSame(null, \Reef\array_first_key([]));
	}
	
	public function test_interpretBool(): void {
		$this->assertTrue(\Reef\interpretBool('yes'));
		$this->assertTrue(\Reef\interpretBool('1'));
		$this->assertTrue(\Reef\interpretBool('true'));
		$this->assertTrue(\Reef\interpretBool('TRUE'));
		$this->assertTrue(\Reef\interpretBool('TrUe'));
		$this->assertTrue(\Reef\interpretBool(1));
		$this->assertTrue(\Reef\interpretBool(1.0));
		$this->assertTrue(\Reef\interpretBool(true));
		
		$this->assertFalse(\Reef\interpretBool('no'));
		$this->assertFalse(\Reef\interpretBool('0'));
		$this->assertFalse(\Reef\interpretBool('false'));
		$this->assertFalse(\Reef\interpretBool('FALSE'));
		$this->assertFalse(\Reef\interpretBool('FaLsE'));
		$this->assertFalse(\Reef\interpretBool(0));
		$this->assertFalse(\Reef\interpretBool(0.0));
		$this->assertFalse(\Reef\interpretBool(false));
	}
	
	public function test_matcherToRegExp(): void {
		$this->assertSame('/^.*$/', \Reef\matcherToRegExp('*'));
		$this->assertSame('/^\\*$/', \Reef\matcherToRegExp('\\*'));
		$this->assertSame('/^.?$/', \Reef\matcherToRegExp('?'));
		$this->assertSame('/^\\?$/', \Reef\matcherToRegExp('\\?'));
		$this->assertSame('/^.$/', \Reef\matcherToRegExp('_'));
		$this->assertSame('/^_$/', \Reef\matcherToRegExp('\\_'));
		$this->assertSame('/^\\\\$/', \Reef\matcherToRegExp('\\'));
		
		$this->assertSame('/^aa.*bb$/', \Reef\matcherToRegExp('aa*bb'));
		$this->assertSame('/^aa\\*bb$/', \Reef\matcherToRegExp('aa\\*bb'));
		$this->assertSame('/^aa.?bb$/', \Reef\matcherToRegExp('aa?bb'));
		$this->assertSame('/^aa\\?bb$/', \Reef\matcherToRegExp('aa\\?bb'));
		$this->assertSame('/^aa.bb$/', \Reef\matcherToRegExp('aa_bb'));
		$this->assertSame('/^aa_bb$/', \Reef\matcherToRegExp('aa\\_bb'));
		$this->assertSame('/^aa\\\\bb$/', \Reef\matcherToRegExp('aa\\bb'));
		
		$this->assertSame('/^.*.*$/', \Reef\matcherToRegExp('**'));
		$this->assertSame('/^\\*\\*$/', \Reef\matcherToRegExp('\\*\\*'));
		$this->assertSame('/^.?.?$/', \Reef\matcherToRegExp('??'));
		$this->assertSame('/^\\?\\?$/', \Reef\matcherToRegExp('\\?\\?'));
		$this->assertSame('/^..$/', \Reef\matcherToRegExp('__'));
		$this->assertSame('/^__$/', \Reef\matcherToRegExp('\\_\\_'));
		$this->assertSame('/^\\\\\\\\$/', \Reef\matcherToRegExp('\\\\'));
		
		$this->assertSame('/^\\\\.*\\\\.*$/', \Reef\matcherToRegExp('\\\\*\\\\*'));
		$this->assertSame('/^\\\\\\*\\\\\\*$/', \Reef\matcherToRegExp('\\\\\\*\\\\\\*'));
		$this->assertSame('/^\\\\.?\\\\.?$/', \Reef\matcherToRegExp('\\\\?\\\\?'));
		$this->assertSame('/^\\\\\\?\\\\\\?$/', \Reef\matcherToRegExp('\\\\\\?\\\\\\?'));
		$this->assertSame('/^\\\\.\\\\.$/', \Reef\matcherToRegExp('\\\\_\\\\_'));
		$this->assertSame('/^\\\\_\\\\_$/', \Reef\matcherToRegExp('\\\\\\_\\\\\\_'));
		$this->assertSame('/^\\\\\\\\\\\\\\\\$/', \Reef\matcherToRegExp('\\\\\\\\'));
		
		$this->assertSame('/^aa\\\\.*bb\\\\.*cc$/', \Reef\matcherToRegExp('aa\\\\*bb\\\\*cc'));
		$this->assertSame('/^aa\\\\\\*bb\\\\\\*cc$/', \Reef\matcherToRegExp('aa\\\\\\*bb\\\\\\*cc'));
		$this->assertSame('/^aa\\\\.?bb\\\\.?cc$/', \Reef\matcherToRegExp('aa\\\\?bb\\\\?cc'));
		$this->assertSame('/^aa\\\\\\?bb\\\\\\?cc$/', \Reef\matcherToRegExp('aa\\\\\\?bb\\\\\\?cc'));
		$this->assertSame('/^aa\\\\.bb\\\\.cc$/', \Reef\matcherToRegExp('aa\\\\_bb\\\\_cc'));
		$this->assertSame('/^aa\\\\_bb\\\\_cc$/', \Reef\matcherToRegExp('aa\\\\\\_bb\\\\\\_cc'));
		$this->assertSame('/^aa\\\\\\\\bb\\\\\\\\cc$/', \Reef\matcherToRegExp('aa\\\\bb\\\\cc'));
		
		$this->assertSame('/^\\\\\\\\\\\\\\\\.*$/', \Reef\matcherToRegExp('\\\\\\\\\\\\\\\\*'));
		$this->assertSame('/^\\\\\\\\\\\\\\\\\\*$/', \Reef\matcherToRegExp('\\\\\\\\\\\\\\\\\\*'));
		$this->assertSame('/^\\\\\\\\\\\\\\\\.?$/', \Reef\matcherToRegExp('\\\\\\\\\\\\\\\\?'));
		$this->assertSame('/^\\\\\\\\\\\\\\\\\\?$/', \Reef\matcherToRegExp('\\\\\\\\\\\\\\\\\\?'));
		$this->assertSame('/^\\\\\\\\\\\\\\\\.$/', \Reef\matcherToRegExp('\\\\\\\\\\\\\\\\_'));
		$this->assertSame('/^\\\\\\\\\\\\\\\\_$/', \Reef\matcherToRegExp('\\\\\\\\\\\\\\\\\\_'));
	}
	
	public function test_rmTree(): void {
		$s_dir = static::STORAGE_DIR . '/rmTree';
		
		// Empty input
		$this->assertTrue(\Reef\rmTree(''));
		
		// Non-existent directory
		$this->assertTrue(\Reef\rmTree('some-non-existent-directory'));
		
		// Existing directory
		mkdir($s_dir);
		
		mkdir($s_dir . '/a');
		mkdir($s_dir . '/b');
		mkdir($s_dir . '/a/c');
		
		touch($s_dir . '/a/asdf.txt');
		touch($s_dir . '/b/fejoi.txt');
		touch($s_dir . '/a/c/eee.txt');
		
		$this->assertTrue(\Reef\rmTree($s_dir));
		
		$this->assertSame(0, count(glob($s_dir . "/*")));
		
		// File
		touch($s_dir . '/fefe.txt');
		$this->assertTrue(\Reef\rmTree($s_dir . '/fefe.txt'));
		$this->assertSame(0, count(glob($s_dir . "/*")));
		
		// Delete root
		$this->assertTrue(\Reef\rmTree($s_dir, true));
		$this->assertFalse(is_dir($s_dir));
	}
}
