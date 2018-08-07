<?php

namespace Reef\Storage;

use Reef\Exception\BadMethodCallException;

class NoStorage implements Storage {
	
	public function __construct() {
		
	}
	
	/**
	 * @inherit
	 */
	public function deleteStorage() {
	}
	
	/**
	 * @inherit
	 */
	public function count() : int {
		return 0;
	}
	
	/**
	 * @inherit
	 */
	public function table(int $i_offset = 0, int $i_num = -1) : array {
		return [];
	}
	
	/**
	 * @inherit
	 */
	public function generator(int $i_offset = 0, int $i_num = -1) : iterable {
	}
	
	/**
	 * @inherit
	 */
	public function addColumns($a_subfields) {
	}
	
	/**
	 * @inherit
	 */
	public function getColumns() : array {
		return [];
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
		throw new BadMethodCallException("No storage attached.");
	}
	
	/**
	 * @inherit
	 */
	public function update(int $i_entryId, array $a_data) {
		throw new BadMethodCallException("No storage attached.");
	}
	
	/**
	 * @inherit
	 */
	public function delete(int $i_entryId) {
		throw new BadMethodCallException("No storage attached.");
	}
	
	/**
	 * @inherit
	 */
	public function get(int $i_entryId) : ?array {
		throw new BadMethodCallException("No storage attached.");
	}
	
	/**
	 * @inherit
	 */
	public function exists(int $i_entryId) : bool {
		return false;
	}
	
	/**
	 * @inherit
	 */
	public function next() : int {
		return -1;
	}
	
}
