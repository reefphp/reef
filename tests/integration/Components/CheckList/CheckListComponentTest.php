<?php

namespace ReefTests\integration\Components\CheckList;

use \ReefTests\integration\Components\ComponentTestCase;

final class CheckListComponentTest extends ComponentTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\CheckList\CheckListComponent;
	}
}
