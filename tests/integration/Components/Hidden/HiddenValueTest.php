<?php

namespace ReefTests\integration\Components\Hidden;

use \ReefTests\integration\Components\FieldValueTestCase;

final class HiddenValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Hidden\HiddenComponent;
	}
}
