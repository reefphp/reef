<?php

namespace tests\Form;

use PHPUnit\Framework\TestCase;
use \Reef\Exception\ConditionException;

final class ConditionTest extends TestCase {
	
	private static $Reef;
	private static $Form;
	private static $Condition;
	
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
		static::$Form = static::$Reef->newTempForm();
		
		static::$Condition = new \Reef\Condition(static::$Form->newSubmission());
		
		$this->assertSame(true, static::$Condition->evaluate(''));
		
		// Check basic conditions
		foreach(['true', 'false'] as $s1) {
			foreach(['and', 'or'] as $s2) {
				foreach(['true', 'false'] as $s3) {
					$this->checkStaticCondition($s1 . ' '.$s2.' ' . $s3);
				}
			}
		}
		
		// Check precedence
		foreach(['true', 'false'] as $s1) {
			foreach(['and', 'or'] as $s2) {
				foreach(['true', 'false'] as $s3) {
					foreach(['and', 'or'] as $s4) {
						foreach(['true', 'false'] as $s5) {
							$this->checkStaticCondition($s1 . ' '.$s2.' ' . $s3 . ' '.$s4.' ' . $s5);
						}
					}
				}
			}
		}
		
		// Complex checks
		$this->checkStaticCondition('(true and false)');
		$this->checkStaticCondition(' ( true or false ) ');
		$this->checkStaticCondition('(true)');
		$this->checkStaticCondition('(((true)))');
		$this->checkStaticCondition(' (( true  ) and false ) ');
		$this->checkStaticCondition('true and true and ((true and false) or false)');
		$this->checkStaticCondition(' true or  true and false and  true  or   true  and false and true and false or true ');
		$this->checkStaticCondition('true and true and (true or (true and false) or false)');
		
	}
	
	public function checkStaticCondition(string $s_condition) {
		$b_ref = eval("return (".$s_condition.");");
		$b_act = static::$Condition->evaluate($s_condition);
		
		$this->assertSame($b_ref, $b_act, "Incorrect result of condition '".$s_condition."'");
	}
	
	
	
}
