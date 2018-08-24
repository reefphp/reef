<?php

namespace tests\Components;

require_once(__DIR__ . '/../ConditionTestCase.php');

final class TextLineConditionTest extends ConditionTestCase {
	
	protected function createComponent() {
		return new \Reef\Components\TextLine\TextLineComponent;
	}
}
