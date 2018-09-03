<?php

namespace Reef\Session;

class PhpSession implements SessionInterface {
	
	protected $s_rootKey;
	
	public function __construct(string $s_rootKey = null) {
		if(session_status() == PHP_SESSION_NONE) {
			throw new \Reef\Exception\BadMethodCallException("Sessions should already have been started");
		}
		
		$this->s_rootKey = $s_rootKey;
	}
	
	public function setReef($Reef) {
		if(empty($this->s_rootKey)) {
			$this->s_rootKey = $Reef->getOption('reef_name');
		}
	}
	
	/**
	 * @inherit
	 */
	public function set(string $s_name, $m_value) {
		$_SESSION[$this->s_rootKey][$s_name] = $m_value;
	}
	
	/**
	 * @inherit
	 */
	public function has(string $s_name) : bool {
		return array_key_exists($s_name, $_SESSION[$this->s_rootKey]??[]);
	}
	
	/**
	 * @inherit
	 */
	public function get(string $s_name, $m_default = null) {
		if($this->has($s_name)) {
			return $_SESSION[$this->s_rootKey][$s_name];
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
		
		$m_value = $_SESSION[$this->s_rootKey][$s_name];
		unset($_SESSION[$this->s_rootKey][$s_name]);
		return $m_value;
	}
}
