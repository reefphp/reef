<?php

namespace ReefTests\integration\Components\Checkbox;

use \ReefTests\integration\Components\FieldTestCase;

final class CheckboxFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Checkbox\CheckboxComponent;
	}
}
