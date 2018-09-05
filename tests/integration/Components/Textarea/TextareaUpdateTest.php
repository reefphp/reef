<?php

namespace ReefTests\integration\Components\Textarea;

use \ReefTests\integration\Components\UpdateTestCase;

final class TextareaUpdateTest extends UpdateTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Textarea\TextareaComponent;
	}
}
