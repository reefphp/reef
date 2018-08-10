<?php

namespace tests\Form;

use PHPUnit\Framework\TestCase;
use Reef\Storage\PDO_SQLite_Storage;
use \Reef\Storage\Storage;
use \Reef\Exception\OutOfBoundsException;

final class StoredFormTest extends TestCase {
	
	const STORAGE_DIR = 'var/tmp/test/sqlite_storage';
	
	private static $Reef;
	private static $Form;
	private static $i_submissionId;
	
	public static function setUpBeforeClass() {
		if(!is_dir(static::STORAGE_DIR)) {
			mkdir(static::STORAGE_DIR, 0777);
		}
	}
	
	public function testCanCreateReef(): void {
		$PDO = new \PDO("sqlite:".static::STORAGE_DIR."/test.db");
		
		// Specify which components we want to use
		$Setup = new \Reef\ReefSetup(
			new \Reef\Storage\PDOStorageFactory($PDO),
			new \Reef\Layout\bootstrap4\bootstrap4()
		);
		
		$this->assertInstanceOf(\Reef\ReefSetup::class, $Setup);
		
		static::$Reef = new \Reef\Reef(
			$Setup,
			[
			]
		);
		
		$this->assertInstanceOf(\Reef\Reef::class, static::$Reef);
	}
	
	/**
	 * @depends testCanCreateReef
	 */
	public function testCanCreateForm(): void {
		static::$Form = static::$Reef->newStoredForm();
		static::$Form->newDefinition([
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
		]);
		
		$this->assertInstanceOf(\Reef\Form::class, static::$Form);
		
		$a_fields = static::$Form->getFields();
		$this->assertSame(3, count($a_fields));
		
		static::$Form->save();
		$this->assertSame(1, count(static::$Reef->getFormIds()));
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testRejectsInvalidSubmission(): void {
		$Submission = static::$Form->newSubmission();
		
		// We have not set the required input_1 parameter, hence it is invalid
		$Submission->fromUserInput([]);
		
		$this->assertSame(false, $Submission->validate());
		
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testCanAddSubmission(): void {
		$Submission = static::$Form->newSubmission();
		
		$this->assertInstanceOf(\Reef\Submission::class, $Submission);
		
		$Submission->fromUserInput([
			'input_1' => 'asdf',
		]);
		
		$this->assertSame(true, $Submission->validate());
		if(!$Submission->validate()) {
			return;
		}
		
		$Submission->save();
		
		$this->assertSame(1, count(static::$Form->getSubmissionIds()));
		
		static::$i_submissionId = $Submission->getSubmissionId();
		
		$Submission2 = static::$Form->getSubmission(static::$i_submissionId);
		
		$this->assertInstanceOf(\Reef\Submission::class, $Submission2);
	}
	
	/**
	 * @depends testCanAddSubmission
	 */
	public function testCanAddField(): void {
		$a_definition = static::$Form->generateDefinition();
		
		$a_definition['fields'][] = [
			'component' => 'reef:text_line',
			'name' => 'input_3',
			'locale' => [
				'title' => 'Input 3',
			],
		];
		
		static::$Form->updateDefinition($a_definition);
		
		$a_fields = static::$Form->getFields();
		$this->assertSame(4, count($a_fields));
		
		$Submission = static::$Form->getSubmission(static::$i_submissionId);
		$this->assertSame(3, count($Submission->toStructured()));
		
		$this->assertSame('', $Submission->getFieldValue('input_3')->toStructured());
	}
	
	/**
	 * @depends testCanAddField
	 */
	public function testCanRemoveField(): void {
		$a_definition = static::$Form->generateDefinition();
		
		foreach($a_definition['fields'] as $i => $a_field) {
			if(isset($a_field['name']) && $a_field['name'] == 'input_1') {
				array_splice($a_definition['fields'], $i, 1);
			}
		}
		
		static::$Form->updateDefinition($a_definition);
		
		$a_fields = static::$Form->getFields();
		$this->assertSame(3, count($a_fields));
		
		$Submission = static::$Form->getSubmission(static::$i_submissionId);
		$this->assertSame(2, count($Submission->toStructured()));
		
		$this->expectException(OutOfBoundsException::class);
		$Submission->getFieldValue('input_1');
	}
	
	/**
	 * @depends testCanRemoveField
	 */
	public function testCanDeleteSubmission(): void {
		$Submission = static::$Form->newSubmission();
		
		$this->assertInstanceOf(\Reef\Submission::class, $Submission);
		
		$Submission->fromUserInput([
			'input_2' => true,
			'input_3' => 'value',
		]);
		
		$this->assertSame(true, $Submission->validate());
		if(!$Submission->validate()) {
			return;
		}
		
		$this->assertSame(1, count(static::$Form->getSubmissionIds()));
		
		$Submission->save();
		
		$this->assertSame(2, count(static::$Form->getSubmissionIds()));
		
		$Submission->delete();
		
		$this->assertSame(1, count(static::$Form->getSubmissionIds()));
	}
	
	/**
	 * @depends testCanDeleteSubmission
	 */
	public function testCanDeleteForm(): void {
		static::$Form->delete();
		
		$this->assertSame(0, count(static::$Reef->getFormIds()));
	}
	
	public static function tearDownAfterClass() {
		unlink(static::STORAGE_DIR."/test.db");
	}
}
