<?php

namespace tests\Components;

require_once(__DIR__ . '/../ConditionTestCase.php');

final class SelectConditionTest extends ConditionTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\Select\SelectComponent;
	}
}
