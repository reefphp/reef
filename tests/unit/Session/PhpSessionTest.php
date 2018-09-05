<?php

namespace ReefTests\unit\Session;

use \Reef\Session\SessionInterface;

final class PhpSessionTest extends SessionTestCase {
	
	public function createSessionObject(): SessionInterface {
		return new \Reef\Session\PhpSession();
	}
}
