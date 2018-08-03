<?php

namespace tests\Components;

require_once(__DIR__ . '/../FieldTestCase.php');

final class TextLineFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextLine\TextLineComponent;
	}
}
