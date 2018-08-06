<?php

namespace tests\Components;

require_once(__DIR__ . '/../UpdateTestCase.php');

final class RadioUpdateTest extends UpdateTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Radio\RadioComponent;
	}
}
