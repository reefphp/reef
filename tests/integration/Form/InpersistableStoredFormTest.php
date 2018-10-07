<?php

namespace ReefTests\integration\Form;

use PHPUnit\Framework\TestCase;
use \Reef\Exception\OutOfBoundsException;
use \Reef\Exception\ResourceNotFoundException;

final class InpersistableStoredFormTest extends TestCase {
	
	private static $Reef;
	private static $Form;
	private static $InpersistableForm;
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
			'storage_name' => 'inpersistable_form_test',
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
	public function testCanGetInpersistable(): void {
		static::$InpersistableForm = static::$Form->toInpersistable();
		
		$this->assertInstanceOf(\Reef\Form\InpersistableStoredForm::class, static::$InpersistableForm);
		
		$this->assertSame(static::$Form->getDefinition()['fields'], static::$InpersistableForm->getDefinition()['fields']);
		$this->assertSame(static::$Form->getFormId(), static::$InpersistableForm->getFormId());
		$this->assertSame(static::$Form->getUUID(), static::$InpersistableForm->getUUID());
		
		
		$InpersistableForm = static::$Reef->getInpersistableForm(static::$Form->getFormId());
		
		$this->assertSame(static::$Form->getDefinition()['fields'], $InpersistableForm->getDefinition()['fields']);
		$this->assertSame(static::$Form->getFormId(), $InpersistableForm->getFormId());
		$this->assertSame(static::$Form->getUUID(), $InpersistableForm->getUUID());
	}
	
	/**
	 * @depends testCanGetInpersistable
	 */
	public function testCanEditInpersistable(): void {
		$Creator = static::$InpersistableForm->newCreator();
		
		$Creator->addField('reef:text_line')
			->setName('input_3')
			->apply();
		
		$a_refDefinition = static::$Form->getDefinition();
		$a_refDefinition['fields'][] = [
			'component' => 'reef:text_line',
			'name' => 'input_3',
		];
		
		$this->assertSame($a_refDefinition['fields'], static::$InpersistableForm->getDefinition()['fields']);
	}
	
	/**
	 * @depends testCanEditInpersistable
	 */
	public function testCannotSetInpersistableStorageName(): void {
		$this->expectException(\Reef\Exception\BadMethodCallException::class);
		
		static::$InpersistableForm->setStorageName('we_should_not_be_able_to_do_this');
	}
	
	public static function tearDownAfterClass() {
		static::$Form->delete();
	}
}
