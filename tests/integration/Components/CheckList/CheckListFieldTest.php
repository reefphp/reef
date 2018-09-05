<?php

namespace ReefTests\integration\Components\CheckList;

use \ReefTests\integration\Components\FieldTestCase;

final class CheckListFieldTest extends FieldTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\CheckList\CheckListComponent;
	}
}
