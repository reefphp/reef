<?php

namespace tests\Components;

require_once(__DIR__ . '/../FieldTestCase.php');

final class CheckboxFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Checkbox\CheckboxComponent;
	}
}
