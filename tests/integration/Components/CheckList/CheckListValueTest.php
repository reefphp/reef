<?php

namespace ReefTests\integration\Components\CheckList;

use \ReefTests\integration\Components\FieldValueTestCase;

final class CheckListValueTest extends FieldValueTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\CheckList\CheckListComponent;
	}
}
