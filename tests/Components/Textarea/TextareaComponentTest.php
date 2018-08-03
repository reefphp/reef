<?php

namespace tests\Components;

require_once(__DIR__ . '/../ComponentTestCase.php');

final class TextareaComponentTest extends ComponentTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Textarea\TextareaComponent;
	}
}