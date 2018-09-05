<?php

namespace ReefTests\integration\Components\Heading;

use \ReefTests\integration\Components\FieldTestCase;

final class HeadingFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Heading\HeadingComponent;
	}
}
