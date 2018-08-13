<?php

namespace tests\Components;

require_once(__DIR__ . '/../UpdateTestCase.php');

final class CheckListUpdateTest extends UpdateTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\CheckList\CheckListComponent;
	}
}
