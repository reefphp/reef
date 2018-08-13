<?php

namespace tests\Components;

require_once(__DIR__ . '/../FieldTestCase.php');

final class SelectFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Select\SelectComponent;
	}
}
