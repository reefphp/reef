<?php

namespace Reef\Session;

class TmpSession implements SessionInterface {
	
	protected $a_data = [];
	
	/**
	 * @inherit
	 */
	public function set(string $s_name, $m_value) {
		$this->a_data[$s_name] = $m_value;
	}
	
	/**
	 * @inherit
	 */
	public function has(string $s_name) : bool {
		return array_key_exists($s_name, $this->a_data);
	}
	
	/**
	 * @inherit
	 */
	public function get(string $s_name, $m_default = null) {
		if($this->has($s_name)) {
			return $this->a_data[$s_name];
		}
		else {
			return $m_default;
		}
	}
	
	/**
	 * @inherit
	 */
	public function remove(string $s_name) {
		if(!$this->has($s_name)) {
			return null;
		}
		
		$m_value = $this->a_data[$s_name];
		unset($this->a_data[$s_name]);
		return $m_value;
	}
}
