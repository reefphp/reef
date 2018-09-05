<?php

namespace ReefTests\integration\Components\Select;

use \ReefTests\integration\Components\FieldValueTestCase;

final class SelectValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Select\SelectComponent;
	}
}
