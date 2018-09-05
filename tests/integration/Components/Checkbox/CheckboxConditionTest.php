<?php

namespace ReefTests\integration\Components\Checkbox;

use \ReefTests\integration\Components\ConditionTestCase;

final class CheckboxConditionTest extends ConditionTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Checkbox\CheckboxComponent;
	}
}
