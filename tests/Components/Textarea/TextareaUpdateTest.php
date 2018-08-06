<?php

namespace tests\Components;

require_once(__DIR__ . '/../UpdateTestCase.php');

final class TextareaUpdateTest extends UpdateTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Textarea\TextareaComponent;
	}
}
