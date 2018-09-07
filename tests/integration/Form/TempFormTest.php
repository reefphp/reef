<?php

namespace ReefTests\integration\Form;

use PHPUnit\Framework\TestCase;

final class TempFormTest extends TestCase {
	
	const CACHE_DIR = 'var/tmp/test/temp_form_test';
	
	private static $Reef;
	private static $Form;
	private static $i_submissionId;
	
	private static $a_testDefinition = [
		'storage_name' => 'test',
		'fields' => [
			[
				'component' => 'reef:heading',
				'size' => 4,
				'locale' => [
					'title' => 'Test form',
				],
			],
			[
				'component' => 'reef:text_line',
				'name' => 'input_1',
				'required' => true,
				'locale' => [
					'title' => 'Input 1',
				],
			],
			[
				'component' => 'reef:checkbox',
				'name' => 'input_2',
				'locale' => [
					'title_left' => 'Input 2',
				],
			],
		],
	];
	
	public static function setUpBeforeClass() {
		if(!is_dir(static::CACHE_DIR)) {
			mkdir(static::CACHE_DIR, 0777);
		}
	}
	
	public static function tearDownAfterClass() {
		\Reef\rmTree(static::CACHE_DIR, true);
	}
	
	public function testCanCreateReef(): void {
		
		// Specify which components we want to use
		$Setup = new \Reef\ReefSetup(
			new \Reef\Storage\NoStorageFactory(),
			new \Reef\Layout\bootstrap4\bootstrap4(),
			new \Reef\Session\TmpSession()
		);
		
		$this->assertInstanceOf(\Reef\ReefSetup::class, $Setup);
		
		static::$Reef = new \Reef\Reef(
			$Setup,
			[
				'cache_dir' => static::CACHE_DIR,
			]
		);
		
		$this->assertInstanceOf(\Reef\Reef::class, static::$Reef);
	}
	
	/**
	 * @depends testCanCreateReef
	 */
	public function testCanCreateForm(): void {
		
		static::$Form = static::$Reef->newTempForm(static::$a_testDefinition);
		
		$this->assertInstanceOf(\Reef\Form\TempForm::class, static::$Form);
		
		// Test definition
		$a_definitionPart = static::$a_testDefinition;
		unset($a_definitionPart['fields']);
		$this->assertSame(static::$Form->getPartialDefinition(), $a_definitionPart);
		$this->assertSame(static::$Form->getDefinition(), static::$a_testDefinition);
		
		// Test getReef
		$this->assertSame(static::$Reef, static::$Form->getReef());
		
		// Test fields functionality
		$a_fields = static::$Form->getFields();
		$this->assertSame(3, count($a_fields));
		
		$a_fields = static::$Form->getValueFields();
		$this->assertSame(2, count($a_fields));
		
		$a_fields = static::$Form->getValueFieldsByName();
		$this->assertSame(array_column(static::$a_testDefinition['fields'], 'name'), array_keys($a_fields));
		
		// Test assets
		for($i=0; $i<2; $i++) {
			// 0: create, 1: cache
			$this->assertInstanceOf(\Reef\FormAssets::class, static::$Form->getFormAssets());
		}
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testCanCreateSubmission(): void {
		$this->assertInstanceOf(\Reef\Submission\TempSubmission::class, static::$Form->newSubmission());
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testCanSetIdPfx(): void {
		$this->assertInternalType('string', static::$Form->getIdPfx());
		
		static::$Form->setIdPfx('some_id_prefix');
		
		$this->assertSame('some_id_prefix', static::$Form->getIdPfx());
	}
	
	/**
	 * @depends testCanSetIdPfx
	 */
	public function testCanCreateHTML(): void {
		// Test HTML
		$this->assertInternalType('string', static::$Form->generateFormHtml());
		
		$Submission = static::$Form->newSubmission();
		$Submission->emptySubmission();
		$this->assertInternalType('string', static::$Form->generateSubmissionHtml($Submission));
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testNoDataLoss(): void {
		// Test trivial data loss
		$this->assertSame([], static::$Form->checkUpdateDataLoss(static::$a_testDefinition));
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testCanUpdateAndMergeDefinition(): void {
		// Test augmented definition
		$a_tmpDefinition = static::$a_testDefinition;
		array_shift($a_tmpDefinition['fields']);
		static::$Form->updateDefinition($a_tmpDefinition);
		$this->assertSame(static::$Form->getDefinition(), $a_tmpDefinition);
		
		// Test merge
		$a_tmpDefinition['locale']['test_key'] = 'test_val';
		static::$Form->mergeDefinition(\Reef\array_subset($a_tmpDefinition, ['locale']));
		$this->assertEquals(static::$Form->getDefinition(), $a_tmpDefinition);
		
		// Set back
		static::$Form->updateDefinition(static::$a_testDefinition);
		$this->assertSame(static::$Form->getDefinition(), static::$a_testDefinition);
	}
	
	/**
	 * @depends testCanCreateReef
	 */
	public function testCanCreateFormFromFile(): void {
		$Form = static::$Reef->getTempFormFactory()->createFromFile(__DIR__ . '/TempFormTest_definition.yml');
		
		$this->assertSame(3, count($Form->getFields()));
	}
	
	/**
	 * @depends testCanCreateReef
	 */
	public function testCannotCreateFormFromMissingFile(): void {
		$this->expectException(\Reef\Exception\ResourceNotFoundException::class);
		
		static::$Reef->getTempFormFactory()->createFromFile(__DIR__ . '/some_missing_file.yml');
	}
	
	/**
	 * @depends testCanCreateReef
	 */
	public function testCanCreateFormFromString(): void {
		$Form = static::$Reef->getTempFormFactory()->createFromString(file_get_contents(__DIR__ . '/TempFormTest_definition.yml'));
		
		$this->assertSame(3, count($Form->getFields()));
	}
	
	/**
	 * @depends testCanCreateReef
	 */
	public function testCanCreateFormFromSetFields(): void {
		$Form = static::$Reef->newTempForm();
		$Form->setFields(static::$a_testDefinition['fields']);
		
		$this->assertSame(3, count($Form->getFields()));
	}
	
	
}
