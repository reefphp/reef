<?php

namespace ReefTests\integration\Components\CheckList;

use \ReefTests\integration\Components\ConditionTestCase;

final class CheckListConditionTest extends ConditionTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\CheckList\CheckListComponent;
	}
}
