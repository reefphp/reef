<?php

namespace ReefTests\integration\Components\Textarea;

use \ReefTests\integration\Components\ComponentTestCase;

final class TextareaComponentTest extends ComponentTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Textarea\TextareaComponent;
	}
}
