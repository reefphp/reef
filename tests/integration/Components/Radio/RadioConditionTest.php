<?php

namespace ReefTests\integration\Components\Radio;

use \ReefTests\integration\Components\ConditionTestCase;

final class RadioConditionTest extends ConditionTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Radio\RadioComponent;
	}
}
