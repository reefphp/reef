<?php

namespace ReefTests\integration\Components\Textarea;

use \ReefTests\integration\Components\ConditionTestCase;

final class TextareaConditionTest extends ConditionTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Textarea\TextareaComponent;
	}
}
