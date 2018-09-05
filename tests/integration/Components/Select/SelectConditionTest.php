<?php

namespace ReefTests\integration\Components\Select;

use \ReefTests\integration\Components\ConditionTestCase;

final class SelectConditionTest extends ConditionTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Select\SelectComponent;
	}
}
