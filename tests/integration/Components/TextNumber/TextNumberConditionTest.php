<?php

namespace ReefTests\integration\Components\TextNumber;

use \ReefTests\integration\Components\ConditionTestCase;

final class TextNumberConditionTest extends ConditionTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextNumber\TextNumberComponent;
	}
}
