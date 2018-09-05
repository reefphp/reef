<?php

namespace ReefTests\integration\Components\TextNumber;

use \ReefTests\integration\Components\UpdateTestCase;

final class TextNumberUpdateTest extends UpdateTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextNumber\TextNumberComponent;
	}
}
