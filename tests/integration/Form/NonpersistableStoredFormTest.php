<?php

namespace ReefTests\integration\Form;

use PHPUnit\Framework\TestCase;
use \Reef\Exception\OutOfBoundsException;
use \Reef\Exception\ResourceNotFoundException;

final class NonpersistableStoredFormTest extends TestCase {
	
	private static $Reef;
	private static $Form;
	private static $NonpersistableForm;
	private static $i_submissionId;
	
	public static function setUpBeforeClass() {
		global $_reef_reef;
		static::$Reef = $_reef_reef;
	}
	
	/**
	 */
	public function testCanCreateForm(): void {
		$i_formIdsBefore = count(static::$Reef->getFormIds());
		
		static::$Form = static::$Reef->newStoredForm();
		static::$Form->updateDefinition([
			'storage_name' => 'nonpersistable_form_test',
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
		
		static::$Form->save();
		
		$a_formIds = static::$Reef->getFormIds();
		$this->assertSame($i_formIdsBefore+1, count($a_formIds));
		$this->assertSame(static::$Form->getDefinition(), static::$Reef->getForm(static::$Form->getFormId())->getDefinition());
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
		$Submission->save();
		
		$this->assertSame(1, static::$Form->getNumSubmissions());
		
		static::$i_submissionId = $Submission->getSubmissionId();
	}
	
	/**
	 * @depends testCanAddSubmission
	 */
	public function testCanGetNonpersistable(): void {
		static::$NonpersistableForm = static::$Form->toNonpersistable();
		
		$this->assertInstanceOf(\Reef\Form\NonpersistableStoredForm::class, static::$NonpersistableForm);
		
		$this->assertSame(static::$Form->getDefinition()['fields'], static::$NonpersistableForm->getDefinition()['fields']);
		$this->assertSame(static::$Form->getFormId(), static::$NonpersistableForm->getFormId());
		$this->assertSame(static::$Form->getUUID(), static::$NonpersistableForm->getUUID());
		
		
		$NonpersistableForm = static::$Reef->getNonpersistableForm(static::$Form->getFormId());
		
		$this->assertSame(static::$Form->getDefinition()['fields'], $NonpersistableForm->getDefinition()['fields']);
		$this->assertSame(static::$Form->getFormId(), $NonpersistableForm->getFormId());
		$this->assertSame(static::$Form->getUUID(), $NonpersistableForm->getUUID());
	}
	
	/**
	 * @depends testCanGetNonpersistable
	 */
	public function testCanEditNonpersistable(): void {
		$Creator = static::$NonpersistableForm->newCreator();
		
		$Creator->addField('reef:text_line')
			->setName('input_3')
			->apply();
		
		$a_refDefinition = static::$Form->getDefinition();
		$a_refDefinition['fields'][] = [
			'component' => 'reef:text_line',
			'name' => 'input_3',
		];
		
		$this->assertSame($a_refDefinition['fields'], static::$NonpersistableForm->getDefinition()['fields']);
	}
	
	/**
	 * @depends testCanEditNonpersistable
	 */
	public function testCannotSetNonpersistableStorageName(): void {
		$this->expectException(\Reef\Exception\BadMethodCallException::class);
		
		static::$NonpersistableForm->setStorageName('we_should_not_be_able_to_do_this');
	}
	
	/**
	 * @depends testCanEditNonpersistable
	 */
	public function testCanGetNonpersistableSubmission(): void {
		
		$Submission = static::$Form->getSubmission(static::$i_submissionId);
		
		$NPSubmission = static::$NonpersistableForm->newNonpersistableSubmission($Submission);
		
		$a_submission = $Submission->toStructured();
		$a_npSubmission = $NPSubmission->toStructured();
		
		$this->assertSame(['input_1', 'input_2'], array_keys($a_submission));
		$this->assertSame(['input_1', 'input_2', 'input_3'], array_keys($a_npSubmission));
		
		$this->assertSame($a_submission, \Reef\array_subset($a_npSubmission, ['input_1', 'input_2']));
	}
	
	public static function tearDownAfterClass() {
		if(!is_null(static::$Form)) {
			static::$Form->delete();
		}
	}
}
