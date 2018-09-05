<?php

namespace ReefTests\integration\Components\Select;

use \ReefTests\integration\Components\FieldTestCase;

final class SelectFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Select\SelectComponent;
	}
}
