<?php

namespace tests\Components;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

abstract class UpdateTestCase extends TestCase {
	
	protected static $Setup;
	protected static $Reef;
	protected static $Component;
	
	const STORAGE_DIR = 'var/tmp/test/update_test_storage';
	const CACHE_DIR = 'var/tmp/test/update_test_cache';
	
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
	
	public function updateProvider() {
		$s_dir = dirname((new \ReflectionClass($this))->getFilename()) . '/updateDeclarations';
		
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
	
	/**
	 * @doesNotPerformAssertions
	 */
	public function testCanBeCreated() {
		static::$Component = $this->createComponent();
		static::$Setup->addComponent(static::$Component);
		
		static::$Reef = new \Reef\Reef(
			static::$Setup,
			[
				'cache_dir' => static::CACHE_DIR,
			]
		);
	}
	
	/**
	 * @depends testCanBeCreated
     * @dataProvider updateProvider
	 */
	public function testUpdates($a_update): void {
		// Check declaration
		static::$Reef->checkDeclaration($a_update['declaration_before']);
		static::$Reef->checkDeclaration($a_update['declaration_after']);
		
		$a_definition = [
			'storage_name' => 'test_form_'.\Reef\unique_id(),
			'fields' => [
				$a_update['declaration_before'],
			],
		];
		
		$Form = static::$Reef->newStoredForm();
		$Form->newDefinition($a_definition);
		$Form->save();
		
		foreach($a_update['values_before'] as $m_value) {
			$Submission = $Form->newSubmission();
			$Submission->emptySubmission();
			$Submission->getFieldValue($a_update['declaration_before']['name'])->fromStructured($m_value);
			$Submission->save();
		}
		
		$a_definition['fields'] = [
			$a_update['declaration_after'],
		];
		
		$a_dataLoss = $Form->checkUpdateDataLoss($a_definition);
		$this->assertSame($a_update['dataloss'], $a_dataLoss);
		
		$Form->updateDefinition($a_definition);
		
		foreach($a_update['values_after'] as $i => $m_value) {
			$Submission = $Form->newSubmission();
			$Submission->load($i+1);
			
			$this->assertSame($m_value, $Submission->getFieldValue($a_update['declaration_after']['name'])->toStructured());
		}
		
		$Form->delete();
		
	}
	
	
}
