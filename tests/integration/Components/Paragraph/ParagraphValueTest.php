<?php

namespace ReefTests\integration\Components\Paragraph;

use \ReefTests\integration\Components\FieldValueTestCase;

final class ParagraphValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Paragraph\ParagraphComponent;
	}
}
