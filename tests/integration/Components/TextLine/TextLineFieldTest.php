<?php

namespace ReefTests\integration\Components\TextLine;

use \ReefTests\integration\Components\FieldTestCase;

final class TextLineFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextLine\TextLineComponent;
	}
}
