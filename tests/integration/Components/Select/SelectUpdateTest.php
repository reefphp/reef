<?php

namespace ReefTests\integration\Components\Select;

use \ReefTests\integration\Components\UpdateTestCase;

final class SelectUpdateTest extends UpdateTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Select\SelectComponent;
	}
}
