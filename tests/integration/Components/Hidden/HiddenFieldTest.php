<?php

namespace ReefTests\integration\Components\Hidden;

use \ReefTests\integration\Components\FieldTestCase;

final class HiddenFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Hidden\HiddenComponent;
	}
}
