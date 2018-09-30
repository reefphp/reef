<?php

namespace ReefTests\integration\Form;

use PHPUnit\Framework\TestCase;
use \Reef\Exception\OutOfBoundsException;
use \Reef\Exception\ResourceNotFoundException;

final class StoredFormTest extends TestCase {
	
	private static $Reef;
	private static $Form;
	private static $i_submissionId;
	
	public static function setUpBeforeClass() {
		global $_reef_reef;
		static::$Reef = $_reef_reef;
	}
	
	/**
	 */
	public function testCanCreateForm(): void {
		static::$Form = static::$Reef->newStoredForm();
		static::$Form->updateDefinition([
			'storage_name' => 'stored_form_test',
			'fields' => [
				[
					'component' => 'reef:heading',
					'level' => 4,
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
						'title' => 'Input 2',
					],
				],
			],
		]);
		
		$this->assertInstanceOf(\Reef\Form\StoredForm::class, static::$Form);
		
		$a_fields = static::$Form->getFields();
		$this->assertSame(3, count($a_fields));
		
		static::$Form->save();
		$a_formIds = static::$Reef->getFormIds();
		$this->assertSame(1, count($a_formIds));
		
		$this->assertSame(static::$Form->getDefinition(), static::$Reef->getForm(reset($a_formIds))->getDefinition());
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testCannotGetInexistentForm(): void {
		$this->expectException(ResourceNotFoundException::class);
		
		static::$Reef->getForm(999999);
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
		
		$this->assertInstanceOf(\Reef\Submission\Submission::class, $Submission);
		
		$Submission->fromUserInput([
			'input_1' => 'asdf',
		]);
		
		$this->assertSame(true, $Submission->validate());
		if(!$Submission->validate()) {
			return;
		}
		
		$Submission->save();
		
		$this->assertSame(1, count(static::$Form->getSubmissionIds()));
		$this->assertSame(1, static::$Form->getNumSubmissions());
		
		static::$i_submissionId = $Submission->getSubmissionId();
		
		$Submission2 = static::$Form->getSubmission(static::$i_submissionId);
		
		$this->assertInstanceOf(\Reef\Submission\Submission::class, $Submission2);
	}
	
	/**
	 * @depends testCanAddSubmission
	 */
	public function testCanAddField(): void {
		$a_definition = static::$Form->getDefinition();
		
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
		$a_definition = static::$Form->getDefinition();
		
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
		
		$this->assertInstanceOf(\Reef\Submission\Submission::class, $Submission);
		
		$Submission->fromUserInput([
			'input_2' => true,
			'input_3' => 'value',
		]);
		
		$this->assertSame(true, $Submission->validate());
		if(!$Submission->validate()) {
			return;
		}
		
		$this->assertSame(1, count(static::$Form->getSubmissionIds()));
		$this->assertSame(1, static::$Form->getNumSubmissions());
		
		$Submission->save();
		
		$this->assertSame(2, count(static::$Form->getSubmissionIds()));
		$this->assertSame(2, static::$Form->getNumSubmissions());
		
		$Submission->delete();
		
		$this->assertSame(1, count(static::$Form->getSubmissionIds()));
		$this->assertSame(1, static::$Form->getNumSubmissions());
	}
	
	/**
	 * @depends testCanDeleteSubmission
	 */
	public function testCanDeleteForm(): void {
		static::$Form->delete();
		
		$this->assertSame(0, count(static::$Reef->getFormIds()));
	}
}
