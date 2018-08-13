<?php

namespace tests\Components;

require_once(__DIR__ . '/../FieldValueTestCase.php');

final class TextLineValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextLine\TextLineComponent;
	}
}
