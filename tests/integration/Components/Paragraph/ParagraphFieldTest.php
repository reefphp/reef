<?php

namespace tests\Components;

require_once(__DIR__ . '/../FieldTestCase.php');

final class ParagraphFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Paragraph\ParagraphComponent;
	}
}
