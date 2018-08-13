<?php

namespace tests\Components;

require_once(__DIR__ . '/../ComponentTestCase.php');

final class HeadingComponentTest extends ComponentTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Heading\HeadingComponent;
	}
}
