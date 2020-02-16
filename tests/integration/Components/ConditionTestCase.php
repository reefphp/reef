<?php

namespace ReefTests\integration\Components;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

abstract class ConditionTestCase extends TestCase {
	
	protected static $Setup;
	protected static $Reef;
	protected static $Component;
	protected static $s_componentName;
	
	const CACHE_DIR = TEST_TMP_DIR . '/component_test_cache';
	
	public static function setUpBeforeClass() {
		global $_reef_PDO;
		
		if(!is_dir(static::CACHE_DIR)) {
			mkdir(static::CACHE_DIR, 0777, true);
		}
		
		// Specify which components we want to use
		static::$Setup = new \Reef\ReefSetup(
			\Reef\Storage\PDOStorageFactory::createFactory($_reef_PDO),
			new \Reef\Layout\bootstrap4\bootstrap4(),
			new \Reef\Session\TmpSession()
		);
	}
	
	public static function tearDownAfterClass() {
		\Reef\rmTree(static::CACHE_DIR, true);
	}
	
	public function declarationProvider() {
		$s_dir = dirname((new \ReflectionClass($this))->getFilename()) . '/conditionDeclarations';
		
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
			yield $s_file => [$a_declaration['declaration'], $a_declaration['valid_conditions']??[], $a_declaration['invalid_conditions']??[]];
		}
	}
	
	abstract protected function createComponent();
	
	public function getReefOptions() {
		return [
			'cache_dir' => static::CACHE_DIR,
		];
	}
	
	public function testCanBeCreated() {
		$Component = $this->createComponent();
		static::$Setup->addComponent($Component);
		
		static::$s_componentName = $Component::COMPONENT_NAME;
		static::$Component = static::$Setup->getComponent(static::$s_componentName);
		
		$this->assertSame($Component, static::$Component);
		
		static::$Reef = new \Reef\Reef(
			static::$Setup,
			$this->getReefOptions()
		);
	}
	
	/**
	 * @depends testCanBeCreated
	 * @dataProvider declarationProvider
	 */
	public function testInvalidConditions($a_declaration, $a_validConditions, $a_invalidConditions): void {
		$Form = static::$Reef->newTempForm([
			'fields' => [
				$a_declaration,
			],
		]);
		
		foreach($a_invalidConditions as $i => $s_invalidCondition) {
			$this->assertFalse($Form->getConditionEvaluator()->validate($s_invalidCondition), "No errors found for invalid condition ".$i);
		}
		
		$this->assertTrue(true);
	}
	
	/**
	 * @depends testCanBeCreated
	 * @dataProvider declarationProvider
	 */
	public function testValidConditions($a_declaration, $a_validConditions, $a_invalidConditions): void {
		$Form = static::$Reef->newTempForm([
			'fields' => [
				$a_declaration,
			],
		]);
		
		$s_fieldName = $a_declaration['name'];
		
		foreach($a_validConditions as $i => $a_validCondition) {
			$a_errors = [];
			$this->assertTrue($Form->getConditionEvaluator()->validate($a_validCondition['condition'], $a_errors), "Errors found for valid condition ".$i.": ".implode(', ', $a_errors));
			
			foreach($a_validCondition['true_for']??[] as $j => $m_trueValue) {
				if(is_callable($m_trueValue)) {
					$m_trueValue = $m_trueValue();
				}
				
				$Submission = $Form->newSubmission();
				$Submission->fromStructured([$s_fieldName => $m_trueValue]);
				$this->assertTrue($Submission->evaluateCondition($a_validCondition['condition']), 'Failed that condition '.$i.' is true for value '.$j);
			}
			
			foreach($a_validCondition['false_for']??[] as $j => $m_falseValue) {
				if(is_callable($m_falseValue)) {
					$m_falseValue = $m_falseValue();
				}
				
				$Submission = $Form->newSubmission();
				$Submission->fromStructured([$s_fieldName => $m_falseValue]);
				$this->assertFalse($Submission->evaluateCondition($a_validCondition['condition']), 'Failed that condition '.$i.' is false for value '.$j);
			}
			
		}
		
		$this->assertTrue(true);
	}
	
	
}
