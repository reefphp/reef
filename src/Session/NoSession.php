<?php

namespace Reef\Session;

class NoSession implements SessionInterface {
	
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
