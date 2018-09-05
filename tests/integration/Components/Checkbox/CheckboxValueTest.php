<?php

namespace ReefTests\integration\Components\Checkbox;

use \ReefTests\integration\Components\FieldValueTestCase;

final class Checkbox extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Checkbox\CheckboxComponent;
	}
}
