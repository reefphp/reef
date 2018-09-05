<?php

namespace ReefTests\unit\Exception;

use PHPUnit\Framework\TestCase;
use \Reef\Exception\ValidationException;

final class ValidationExceptionTest extends TestCase {
	
	public function testException(): void {
		$a_errors = [
			'error_1' => 'text_1',
			'error_2' => [
				43 => [
					'sub_43' => 'text_2',
				],
			],
		];
		
		$Exception = new ValidationException($a_errors);
		
		$this->assertSame($a_errors, $Exception->getErrors());
		
		$s_message = $Exception->getMessage();
		
		$b_success = true;
		foreach(['error_1', 'text_1', 'error_2', 'sub_43', 'text_2'] as $s_name) {
			if(strpos($s_message, $s_name) === false) {
				$b_success = false;
			}
		}
		
		$this->assertTrue($b_success);
	}
	
}
