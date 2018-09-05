<?php

namespace ReefTests\integration\Components\Textarea;

use \ReefTests\integration\Components\FieldValueTestCase;

final class TextareaValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Textarea\TextareaComponent;
	}
}
