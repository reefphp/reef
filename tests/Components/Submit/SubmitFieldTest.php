<?php

namespace tests\Components;

require_once(__DIR__ . '/../FieldTestCase.php');

final class SubmitFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Submit\SubmitComponent;
	}
}
