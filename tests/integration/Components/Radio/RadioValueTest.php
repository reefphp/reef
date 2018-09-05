<?php

namespace ReefTests\integration\Components\Radio;

use \ReefTests\integration\Components\FieldValueTestCase;

final class RadioValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Radio\RadioComponent;
	}
}
