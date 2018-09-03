<?php

namespace tests\Session;

use \Reef\Session\SessionInterface;

require_once(__DIR__ . '/SessionTestCase.php');

final class TmpSessionTest extends SessionTestCase {
	
	public function createSessionObject(): SessionInterface {
		return new \Reef\Session\TmpSession();
	}
}
