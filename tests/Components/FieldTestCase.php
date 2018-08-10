<?php

namespace tests\Components;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

abstract class FieldTestCase extends TestCase {
	
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
			new \Reef\Storage\PDOStorageFactory($PDO),
			new \Reef\Layout\bootstrap4\bootstrap4()
		);
	}
	
	public static function tearDownAfterClass() {
		unlink(static::STORAGE_DIR."/test.db");
		\Reef\rmTree(static::CACHE_DIR, true);
	}
	
	public function invalidDeclarationProvider() {
		return $this->declarationProvider(false);
	}
	
	public function validDeclarationProvider() {
		return $this->declarationProvider(true);
	}
	
	protected function declarationProvider(bool $b_valid) {
		$s_dir = dirname((new \ReflectionClass($this))->getFilename());
		$s_dir .= $b_valid ? '/validDeclarations' : '/invalidDeclarations';
		
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
			yield $s_file => [array_diff_key($a_declaration, ['valid_values', 'invalid_values'])];
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
     * @dataProvider invalidDeclarationProvider
	 */
	public function testInvalidDeclarations($a_declaration): void {
		$this->expectException(\Reef\Exception\ValidationException::class);
		
		static::$Reef->checkDeclaration($a_declaration);
		
		static::$Component->newField($a_declaration, static::$Reef->newTempForm());
	}
	
	/**
	 * @depends testCanBeCreated
     * @dataProvider validDeclarationProvider
	 */
	public function testValidDeclarations($a_declaration): void {
		// Check declaration
		static::$Reef->checkDeclaration($a_declaration);
		
		$Field = static::$Component->newField($a_declaration, static::$Reef->newTempForm());
		
		$this->assertInstanceOf(\Reef\Components\Field::class, $Field);
		
		// Test flat structure
		$a_flatStructure = $Field->getFlatStructure();
		foreach($a_flatStructure as $m_column => $a_column) {
			if(is_numeric($m_column)) {
				if(count($a_flatStructure) > 1) {
					$this->fail('Flat structure may only contain a numeric index of 0 when there is only one element.');
				}
				if($m_column != 0) {
					$this->fail('A flat structure numeric index must equal 0');
				}
			}
			else if(!preg_match('/'.str_replace('/', '\\/', \Reef\Reef::NAME_REGEXP).'/', $m_column)) {
				$this->fail('Invalid flat structure name "'.$m_column.'"');
			}
			
			if(empty($a_column['type']) || !in_array($a_column['type'], \Reef\Storage\Storage::TYPES)) {
				$this->fail('Invalid storage type "'.$a_column['type'].'" for flat structure column "'.$m_column.'"');
			}
		}
		
		$this->assertInternalType('bool', $Field->isRequired());
		
		// Test column names
		$this->assertSame($Field->dataFieldNamesToColumnNames(), array_flip($Field->columnNamesToDataFieldNames()));
	}
	
	
}
