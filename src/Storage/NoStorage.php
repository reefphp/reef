<?php

namespace Reef\Storage;

use Reef\Exception\IOException;

class NoStorage implements Storage {
	
	public function __construct() {
		
	}
	
	/**
	 * @inherit
	 */
	public function list() : array {
		return [];
	}
	
	/**
	 * @inherit
	 */
	public function insert(array $a_data) : int {
		throw new IOException("No storage attached.");
	}
	
	/**
	 * @inherit
	 */
	public function update(int $i_entryId, array $a_data) {
		throw new IOException("No storage attached.");
	}
	
	/**
	 * @inherit
	 */
	public function delete(int $i_entryId) {
		throw new IOException("No storage attached.");
	}
	
	/**
	 * @inherit
	 */
	public function get(int $i_entryId) : array {
		throw new IOException("No storage attached.");
	}
	
	/**
	 * @inherit
	 */
	public function exists(int $i_entryId) : bool {
		return false;
	}
	
}
