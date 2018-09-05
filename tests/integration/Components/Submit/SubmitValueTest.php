<?php

namespace ReefTests\integration\Components\Submit;

use \ReefTests\integration\Components\FieldValueTestCase;

final class SubmitValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Submit\SubmitComponent;
	}
}
