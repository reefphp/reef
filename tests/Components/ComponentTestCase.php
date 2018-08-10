<?php

namespace tests\Components;

use PHPUnit\Framework\TestCase;

abstract class ComponentTestCase extends TestCase {
	
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
	 */
	public function testConfiguration(): void {
		$a_configuration = static::$Component->getConfiguration();
		
		// Test required configuration
		foreach(['vendor', 'name', 'category'] as $s_confKey) {
			$this->assertTrue(!empty($a_configuration[$s_confKey]) && is_string($a_configuration[$s_confKey]));
		}
		
		$this->assertTrue(!empty($a_configuration['assets']['component_image']) && is_string($a_configuration['assets']['component_image']));
		
		// Test other configuration types
		foreach(['assets', 'basicLocale', 'advancedLocale', 'internalLocale', 'basicDefinition', 'advancedDefinition', 'props'] as $s_confKey) {
			$this->assertTrue(empty($a_configuration[$s_confKey]) || is_array($a_configuration[$s_confKey]));
		}
		
		// Test locale presence
		$a_localeKeys = array_merge(
			array_column($a_configuration['basicLocale']??[], 'title_key'),
			array_column($a_configuration['advancedLocale']??[], 'title_key'),
			array_keys($a_configuration['internalLocale']??[])
		);
		$a_missingLocaleKeys = array_filter(static::$Component->transMultiple($a_localeKeys), function($s_val) { return $s_val === null; });
		$this->assertTrue(empty($a_missingLocaleKeys), "Missing locale keys ".implode(', ', $a_missingLocaleKeys));
	}
	
	/**
	 * @depends testConfiguration
	 */
	public function testAssetsExist(): void {
		$a_assets = static::$Component->getAssets();
		
		$a_inexistent = [];
		
		foreach($a_assets as $s_assetFile) {
			if(!file_exists(static::$Component::getDir() . '/' . $s_assetFile)) {
				$a_inexistent[] = $s_assetFile;
			}
		}
		
		$this->assertTrue(empty($a_inexistent), "Missing files ".implode(', ', $a_inexistent));
	}
	
	/**
	 * @depends testConfiguration
	 */
	public function testVarAssetsExist(): void {
		$a_assets = array_merge(static::$Component->getJS(), static::$Component->getCSS());
		
		$a_inexistent = [];
		
		foreach($a_assets as $a_asset) {
			if($a_asset['type'] == 'local' && !file_exists($a_asset['path'])) {
				$a_inexistent[] = $a_asset['path'];
			}
		}
		
		$this->assertTrue(empty($a_inexistent), "Missing files ".implode(', ', $a_inexistent));
	}
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testRequiredComponents(): void {
		$a_requiredComponents = static::$Component->requiredComponents();
		
		$this->assertTrue(is_array($a_requiredComponents));
	}
	
	/**
	 * @depends testRequiredComponents
	 * @doesNotPerformAssertions
	 */
	public function testDefinitions(): void {
		$a_configuration = static::$Component->getConfiguration();
		
		if(!empty($a_configuration['basicDefinition'])) {
			static::$Reef->checkDefinition($a_configuration['basicDefinition']);
		}
		if(!empty($a_configuration['advancedDefinition'])) {
			static::$Reef->checkDefinition($a_configuration['advancedDefinition']);
		}
	}
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testInheritance(): void {
		$this->assertTrue(empty(static::$Component::PARENT_NAME) == empty(static::$Component->getParent()));
		$this->assertTrue(empty(static::$Component::PARENT_NAME) || (count(static::$Component->getInheritanceList()) > 1));
	}
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testTemplate(): void {
		$s_template = static::$Component->getTemplate();
		
		$this->assertTrue(!empty($s_template));
	}
	
}
