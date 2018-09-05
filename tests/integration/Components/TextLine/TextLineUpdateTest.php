<?php

namespace ReefTests\integration\Components\TextLine;

use \ReefTests\integration\Components\UpdateTestCase;

final class TextLineUpdateTest extends UpdateTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextLine\TextLineComponent;
	}
}
