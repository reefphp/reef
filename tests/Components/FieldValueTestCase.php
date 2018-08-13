<?php

namespace tests\Components;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

abstract class FieldValueTestCase extends TestCase {
	
	protected static $Setup;
	protected static $Reef;
	protected static $Component;
	protected static $s_componentName;
	
	const STORAGE_DIR = 'var/tmp/test/component_test_storage';
	const CACHE_DIR = 'var/tmp/test/component_test_cache';
	
	public static function setUpBeforeClass() {
		if(!is_dir(static::STORAGE_DIR)) {
			mkdir(static::STORAGE_DIR, 0777);
		}
		if(!is_dir(static::CACHE_DIR)) {
			mkdir(static::CACHE_DIR, 0777);
		}
		
		$PDO = new \PDO("sqlite:".static::STORAGE_DIR."/test.db");
		
		// Specify which components we want to use
		static::$Setup = new \Reef\ReefSetup(
			\Reef\Storage\PDOStorageFactory::createFactory($PDO),
			new \Reef\Layout\bootstrap4\bootstrap4()
		);
	}
	
	public static function tearDownAfterClass() {
		unlink(static::STORAGE_DIR."/test.db");
		\Reef\rmTree(static::CACHE_DIR, true);
	}
	
	public function declarationProvider() {
		$s_dir = dirname((new \ReflectionClass($this))->getFilename()) . '/validDeclarations';
		
		if(!is_dir($s_dir)) {
			return;
		}
		
		$Iterator = new \DirectoryIterator($s_dir);
		foreach ($Iterator as $fileinfo) {
			$s_file = $fileinfo->getFilename();
			if($fileinfo->isDot() || !$fileinfo->isFile() || substr($s_file, -4) != '.yml') {
				continue;
			}
			$a_declaration = Yaml::parse(file_get_contents($s_dir.'/'.$s_file))??[];
			yield $s_file => [array_diff_key($a_declaration, ['valid_values', 'invalid_values']), $a_declaration['valid_values']??[], $a_declaration['invalid_values']??[]];
		}
	}
	
	abstract protected function createComponent();
	
	public function testCanBeCreated() {
		$Component = $this->createComponent();
		static::$Setup->addComponent($Component);
		
		static::$s_componentName = $Component::COMPONENT_NAME;
		static::$Component = static::$Setup->getComponent(static::$s_componentName);
		
		$this->assertSame($Component, static::$Component);
		
		static::$Reef = new \Reef\Reef(
			static::$Setup,
			[
				'cache_dir' => static::CACHE_DIR,
			]
		);
	}
	
	/**
	 * @depends testCanBeCreated
     * @dataProvider declarationProvider
	 */
	public function testDefaultValue($a_declaration, $a_validValues, $a_invalidValues): void {
		$Field = static::$Component->newField($a_declaration, static::$Reef->newTempForm());
		
		$Value = $Field->newValue();
		$Value->fromDefault();
		
		$this->assertTrue($Value->isDefault());
	}
	
	/**
	 * @depends testCanBeCreated
     * @dataProvider declarationProvider
	 */
	public function testInvalidValues($a_declaration, $a_validValues, $a_invalidValues): void {
		$Field = static::$Component->newField($a_declaration, static::$Reef->newTempForm());
		
		foreach($a_invalidValues as $i => $m_invalidValue) {
			$Value = $Field->newValue();
			$Value->fromUserInput($m_invalidValue);
			$this->assertFalse($Value->validate(), "No errors found for invalid value ".$i);
		}
		
		$this->assertTrue(true);
	}
	
	/**
	 * @depends testCanBeCreated
     * @dataProvider declarationProvider
	 */
	public function testValidValues($a_declaration, $a_validValues, $a_invalidValues): void {
		$Field = static::$Component->newField($a_declaration, static::$Reef->newTempForm());
		
		foreach($a_validValues as $i => $m_validValue) {
			$Value = $Field->newValue();
			$Value->fromUserInput($m_validValue);
			$this->assertTrue($Value->validate(), "Errors found for valid value ".$i.": ".implode('; ', $Value->getErrors()??[]));
			
			// Test structured
			$m_structured = $Value->toStructured();
			$NewValue = $Field->newValue();
			$this->assertEquals($m_structured, $NewValue->toStructured($NewValue->fromStructured($m_structured)), "toStructured is not the inverse of fromStructured");
			
			// Test flat
			$a_flat = $Value->toFlat();
			$NewValue = $Field->newValue();
			$this->assertEquals($a_flat, $NewValue->toFlat($NewValue->fromFlat($a_flat)), "toFlat is not the inverse of fromFlat");
			
			$this->assertEquals(array_keys($a_flat), array_keys($Field->getFlatStructure()), "Flat column keys do not match");
			
			// Test overview
			$this->assertEquals(array_keys($Value->toOverviewColumns()), array_keys($Field->getOverviewColumns()), "Overview column keys do not match");
			
			// Test view_form()
			$this->assertInternalType('array', $Field->view_form($Value));
			
			// Test view_submission()
			$this->assertInternalType('array', $Field->view_submission($Value));
			
		}
		
		$this->assertTrue(true);
	}
	
	
}
