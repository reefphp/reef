<?php

namespace ReefTests\unit\Session;

use \Reef\Session\SessionInterface;

final class TmpSessionTest extends SessionTestCase {
	
	public function createSessionObject(): SessionInterface {
		return new \Reef\Session\TmpSession();
	}
}
