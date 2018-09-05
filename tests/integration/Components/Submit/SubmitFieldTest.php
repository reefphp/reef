<?php

namespace ReefTests\integration\Components\Submit;

use \ReefTests\integration\Components\FieldTestCase;

final class SubmitFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Submit\SubmitComponent;
	}
}
