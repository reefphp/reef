<?php

namespace ReefTests\integration\Components\TextLine;

use \ReefTests\integration\Components\FieldValueTestCase;

final class TextLineValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextLine\TextLineComponent;
	}
}
