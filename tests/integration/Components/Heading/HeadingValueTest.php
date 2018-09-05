<?php

namespace ReefTests\integration\Components\Heading;

use \ReefTests\integration\Components\FieldValueTestCase;

final class HeadingValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Heading\HeadingComponent;
	}
}
