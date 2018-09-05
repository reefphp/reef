<?php

namespace ReefTests\integration\Components\Radio;

use \ReefTests\integration\Components\UpdateTestCase;

final class RadioUpdateTest extends UpdateTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Radio\RadioComponent;
	}
}
