<?php

namespace ReefTests\integration\Components\Textarea;

use \ReefTests\integration\Components\FieldTestCase;

final class TextareaFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Textarea\TextareaComponent;
	}
}
