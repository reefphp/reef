<?php

namespace ReefTests\integration\Components\TextNumber;

use \ReefTests\integration\Components\FieldTestCase;

final class TextNumberFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextNumber\TextNumberComponent;
	}
}
