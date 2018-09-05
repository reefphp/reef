<?php

namespace ReefTests\integration\Components\TextLine;

use \ReefTests\integration\Components\ConditionTestCase;

final class TextLineConditionTest extends ConditionTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextLine\TextLineComponent;
	}
}
