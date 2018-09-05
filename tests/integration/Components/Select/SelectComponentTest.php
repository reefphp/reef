<?php

namespace ReefTests\integration\Components\Select;

use \ReefTests\integration\Components\ComponentTestCase;

final class SelectComponentTest extends ComponentTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Select\SelectComponent;
	}
}
