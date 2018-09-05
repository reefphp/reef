<?php

namespace ReefTests\integration\Components\TextNumber;

use \ReefTests\integration\Components\FieldValueTestCase;

final class TextNumberValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextNumber\TextNumberComponent;
	}
}
