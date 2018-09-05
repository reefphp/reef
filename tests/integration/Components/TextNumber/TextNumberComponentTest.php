<?php

namespace ReefTests\integration\Components\TextNumber;

use \ReefTests\integration\Components\ComponentTestCase;

final class TextNumberComponentTest extends ComponentTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextNumber\TextNumberComponent;
	}
}
