<?php

namespace Reef\Session;

/**
 * Session implementation using no implementation. Can be used when using
 * components that do not require sessions.
 */
class NoSession implements SessionInterface {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		
	}
	
	/**
	 * @inherit
	 */
	public function set(string $s_name, $m_value) {
		throw new \Reef\Exception\BadMethodCallException("Sessions not available");
	}
	
	/**
	 * @inherit
	 */
	public function has(string $s_name) : bool {
		throw new \Reef\Exception\BadMethodCallException("Sessions not available");
	}
	
	/**
	 * @inherit
	 */
	public function get(string $s_name, $m_default = null) {
		throw new \Reef\Exception\BadMethodCallException("Sessions not available");
	}
	
	/**
	 * @inherit
	 */
	public function remove(string $s_name) {
		throw new \Reef\Exception\BadMethodCallException("Sessions not available");
	}
}
