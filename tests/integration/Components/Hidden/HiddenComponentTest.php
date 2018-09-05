<?php

namespace ReefTests\integration\Components\Hidden;

use \ReefTests\integration\Components\ComponentTestCase;

final class HiddenComponentTest extends ComponentTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Hidden\HiddenComponent;
	}
}
