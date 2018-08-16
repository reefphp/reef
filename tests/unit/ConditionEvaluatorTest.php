<?php

namespace tests\Form;

use PHPUnit\Framework\TestCase;
use \Reef\Exception\ConditionException;

final class ConditionEvaluatorTest extends TestCase {
	
	private static $Reef;
	private static $Form;
	private static $Submission;
	private static $ConditionEvaluator;
	
	public static function setUpBeforeClass() {
		
		// Specify which components we want to use
		$Setup = new \Reef\ReefSetup(
			new \Reef\Storage\NoStorageFactory(),
			new \Reef\Layout\bootstrap4\bootstrap4()
		);
		
		static::$Reef = new \Reef\Reef(
			$Setup,
			[
			]
		);
	}
	
	/**
	 */
	public function testStaticConditions(): void {
		$Submission = static::$Reef->newTempForm()->newSubmission();
		
		$fn_checkStaticCondition = function(string $s_condition) use($Submission) {
			$b_ref = eval("return (".$s_condition.");");
			$b_act = $Submission->evaluateCondition($s_condition);
			
			$this->assertSame($b_ref, $b_act, "Incorrect result of condition '".$s_condition."'");
		};
		
		$this->assertSame(true, $Submission->evaluateCondition(''));
		
		// Check basic conditions
		foreach(['true', 'false'] as $s1) {
			foreach(['and', 'or'] as $s2) {
				foreach(['true', 'false'] as $s3) {
					$fn_checkStaticCondition($s1 . ' '.$s2.' ' . $s3);
				}
			}
		}
		
		// Check precedence
		foreach(['true', 'false'] as $s1) {
			foreach(['and', 'or'] as $s2) {
				foreach(['true', 'false'] as $s3) {
					foreach(['and', 'or'] as $s4) {
						foreach(['true', 'false'] as $s5) {
							$fn_checkStaticCondition($s1 . ' '.$s2.' ' . $s3 . ' '.$s4.' ' . $s5);
						}
					}
				}
			}
		}
		
		// Complex checks
		$fn_checkStaticCondition('(true and false)');
		$fn_checkStaticCondition(' ( true or false ) ');
		$fn_checkStaticCondition('(true)');
		$fn_checkStaticCondition('(((true)))');
		$fn_checkStaticCondition(' (( true  ) and false ) ');
		$fn_checkStaticCondition('true and true and ((true and false) or false)');
		$fn_checkStaticCondition(' true or  true and false and  true  or   true  and false and true and false or true ');
		$fn_checkStaticCondition('true and true and (true or (true and false) or false)');
		$fn_checkStaticCondition('true and 1');
		$fn_checkStaticCondition('0 or true');
		
	}
	
	/**
	 */
	public function testCatchesEndOfLine(): void {
		$Submission = static::$Reef->newTempForm()->newSubmission();
		
		$this->expectException(\Reef\Exception\ConditionException::class);
		$this->expectExceptionMessage('Unexpected end of line');
		
		$Submission->evaluateCondition('true and ');
	}
	
	/**
	 */
	public function testCatchesUnexpectedToken(): void {
		$Submission = static::$Reef->newTempForm()->newSubmission();
		
		$this->expectException(\Reef\Exception\ConditionException::class);
		$this->expectExceptionMessage('Unexpected token');
		
		$Submission->evaluateCondition('true and true true');
	}
	
	/**
	 */
	public function testCatchesEmptySubCondition(): void {
		$Submission = static::$Reef->newTempForm()->newSubmission();
		
		$this->expectException(\Reef\Exception\ConditionException::class);
		$this->expectExceptionMessage('Caught runaway argument');
		
		$Submission->evaluateCondition('true and ( true ');
	}
	
	/**
	 */
	public function testCatchesInvalidFieldname(): void {
		$Submission = static::$Reef->newTempForm()->newSubmission();
		
		$this->expectException(\Reef\Exception\ConditionException::class);
		$this->expectExceptionMessage('Invalid field name');
		
		$Submission->evaluateCondition('true or inexistent_field equals "asdf" ');
	}
	
	/**
	 */
	public function testCatchesNoConditionSupport(): void {
		$Form = static::$Reef->newTempForm();
		$Form->newCreator()
			->addField('reef:option_list')
				->setName('field1')
			->apply();
		
		$Submission = $Form->newSubmission();
		$Submission->fromStructured([
			'field1' => [],
		]);
		
		$this->expectException(\Reef\Exception\ConditionException::class);
		$this->expectExceptionMessage('does not support conditions');
		
		$Submission->evaluateCondition('true or field1 equals "asdf" ');
	}
	
	public function invalidStaticInputProvider() {
		return [
			['true ) or false'],
			['true or () or false'],
			['true ( or ) or false'],
			['true ( or false )'],
			[' ( true or ) false'],
			['true ) or ( false '],
			['(true or ( true or true ) '],
			['true () '],
			['()'],
			['('],
			[')'],
		];
	}
	
	/**
	 * @dataProvider invalidStaticInputProvider
	 */
	public function testCatchesInvalidStaticInput($s_condition): void {
		$Submission = static::$Reef->newTempForm()->newSubmission();
		
		$this->expectException(\Reef\Exception\ConditionException::class);
		
		$Submission->evaluateCondition($s_condition);
	}
	
	/**
	 */
	public function testSingleCondition(): void {
		$Form = static::$Reef->newTempForm();
		$Form->newCreator()
			->addField('reef:text_line')
				->setName('field1')
			->apply();
		
		$Submission = $Form->newSubmission();
		$Submission->fromStructured([
			'field1' => 'Some input text',
		]);
		
		$this->assertTrue($Submission->evaluateCondition('field1 equals "Some input text"'));
		$this->assertFalse($Submission->evaluateCondition('field1 does not equal "Some input text"'));
		$this->assertFalse($Submission->evaluateCondition('field1 equals "asdf"'));
		$this->assertTrue($Submission->evaluateCondition('field1 does not equal "asdf"'));
		
		$this->assertTrue($Submission->evaluateCondition('field1 contains " input "'));
		$this->assertFalse($Submission->evaluateCondition('field1 does not contain " input "'));
		$this->assertFalse($Submission->evaluateCondition('field1 contains " asdf "'));
		$this->assertTrue($Submission->evaluateCondition('field1 does not contain " asdf "'));
	}
	
	/**
	 */
	public function testCatchesInvalidOperator(): void {
		$Form = static::$Reef->newTempForm();
		$Form->newCreator()
			->addField('reef:text_line')
				->setName('field1')
			->apply();
		
		$Submission = $Form->newSubmission();
		$Submission->fromStructured([
			'field1' => 'Some input text',
		]);
		
		$this->expectException(\Reef\Exception\ConditionException::class);
		$this->expectExceptionMessage('Invalid operator');
		
		$Submission->evaluateCondition('field1 invalid_operator "something"');
	}
	
	/**
	 */
	public function testCompoundCondition(): void {
		$Form = static::$Reef->newTempForm();
		$Form->newCreator()
			->addField('reef:text_line')
				->setName('field1')
			->addField('reef:text_line')
				->setName('field2')
			->addField('reef:text_line')
				->setName('field3')
			->apply();
		
		$Submission = $Form->newSubmission();
		$Submission->fromStructured([
			'field1' => 'Some input text',
			'field2' => 'Another text',
			'field3' => '',
		]);
		
		$this->assertTrue($Submission->evaluateCondition('field1 equals "Some input text" and field2 equals "Another text"'));
		$this->assertTrue($Submission->evaluateCondition('field1 contains "text" and field2 contains "text"'));
		$this->assertTrue($Submission->evaluateCondition('field1 contains "input" or field2 contains "input"'));
		$this->assertFalse($Submission->evaluateCondition('field1 contains "asdf" or field2 contains "asdf" or field3 contains "asdf"'));
		$this->assertTrue($Submission->evaluateCondition('field3 equals ""'));
	}
	
	/**
	 */
	public function testDisallowsNonrelatedObjects(): void {
		$Form = static::$Reef->newTempForm();
		$Submission = static::$Reef->newTempForm()->newSubmission();
		$ConditionEvaluator = new \Reef\ConditionEvaluator($Form);
		
		$this->expectException(\Reef\Exception\BadMethodCallException::class);
		
		$ConditionEvaluator->evaluate($Submission, '');
	}
	
	public function validComplexInputProvider() {
		return [
			[true,  'field contains "&"'],
			[true,  'field contains "\\""'],
			[true,  'field contains "\\\\\\""'],
			[true,  'field contains "\\\\\\\\\\""'],
			[false, 'field contains "\\\\\\\\\\\\\\""'],
			[true,  'field contains "\'"'],
			[true,  'field contains "\\\\\'"'],
			[true,  'field contains "\\\\\\\\\'"'],
			[false, 'field contains "\\\\\\\\\\\\\'"'],
			[true,  'field contains "[ ie] { } > < ( ( ) )) )"'],
		];
	}
	
	/**
	 * @dataProvider validComplexInputProvider
	 */
	public function testValidComplexStrings($b_result, $s_condition): void {
		$Form = static::$Reef->newTempForm();
		$Form->newCreator()
			->addField('reef:text_line')
				->setName('field')
			->apply();
		
		$Submission = $Form->newSubmission();
		$Submission->fromStructured([
			'field' => ' & f e%% !@#$%^&*() " \' \\\\\' #$ ~` [ ie] { } > < ( ( ) )) ) \\\\"',
		]);
		
		$this->assertSame($b_result, $Submission->evaluateCondition($s_condition));
	}
	
	public function invalidComplexInputProvider() {
		return [
			['field contains "\\\'"'],
			['field contains "\\"'],
			['field contains """'],
			['field contains "'],
		];
	}
	
	/**
	 * @dataProvider invalidComplexInputProvider
	 */
	public function testInvalidComplexStrings( $s_condition): void {
		$Form = static::$Reef->newTempForm();
		$Form->newCreator()
			->addField('reef:text_line')
				->setName('field')
			->apply();
		
		$Submission = $Form->newSubmission();
		$Submission->fromStructured([
			'field' => ' & f e%% !@#$%^&*() " \' \\\\\' #$ ~` [ ie] { } > < ( ( ) )) ) \\\\"',
		]);
		
		$this->expectException(\Reef\Exception\ConditionException::class);
		
		$Submission->evaluateCondition($s_condition);
	}
	
	
	
}
