<?php

namespace ReefTests\integration\Components\CheckList;

use \ReefTests\integration\Components\UpdateTestCase;

final class CheckListUpdateTest extends UpdateTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\CheckList\CheckListComponent;
	}
}
