<?php

namespace ReefTests\integration\Components\Radio;

use \ReefTests\integration\Components\FieldTestCase;

final class RadioFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Radio\RadioComponent;
	}
}
