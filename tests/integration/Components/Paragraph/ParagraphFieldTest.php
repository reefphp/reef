<?php

namespace ReefTests\integration\Components\Paragraph;

use \ReefTests\integration\Components\FieldTestCase;

final class ParagraphFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Paragraph\ParagraphComponent;
	}
}
