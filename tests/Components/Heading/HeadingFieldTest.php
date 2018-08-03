<?php

namespace tests\Components;

require_once(__DIR__ . '/../FieldTestCase.php');

final class HeadingFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Heading\HeadingComponent;
	}
}
