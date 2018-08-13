<?php

namespace tests\Components;

require_once(__DIR__ . '/../ComponentTestCase.php');

final class HiddenComponentTest extends ComponentTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Hidden\HiddenComponent;
	}
}
