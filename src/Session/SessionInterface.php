<?php

namespace Reef\Session;

/**
 * Interface for session data manipulation
 */
interface SessionInterface {
	
	/**
	 * Set a session value
	 * @param string $s_name The value name
	 * @param mixed $m_value The value
	 */
	public function set(string $s_name, $m_value);
	
	/**
	 * Determine whether a session value exists
	 * @param string $s_name The value name
	 * @return bool True if it exists
	 */
	public function has(string $s_name) : bool;
	
	/**
	 * Get a session value
	 * @param string $s_name The value name
	 * @param mixed $m_default A default value to return if the value does not exist, null by default
	 * @return mixed The value
	 */
	public function get(string $s_name, $m_default = null);
	
	/**
	 * Remove a session value
	 * @param string $s_name The value name
	 * @return mixed The removed value
	 */
	public function remove(string $s_name);
}
