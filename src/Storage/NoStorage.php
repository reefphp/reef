<?php

namespace Reef\Storage;

use Reef\Exception\BadMethodCallException;

/**
 * Storage implementation that can be used to not use any storage.
 * This may be useful if you only want to use temporary forms.
 */
class NoStorage implements Storage {
	
	/**
	 * Constructor
	 */
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
		return new \EmptyIterator();
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
	public function get(int $i_entryId) : array {
		throw new BadMethodCallException("No storage attached.");
	}
	
	/**
	 * @inherit
	 */
	public function getOrNull(int $i_entryId) : ?array {
		return null;
	}
	
	/**
	 * @inherit
	 */
	public function getByUUID(string $s_uuid) : array {
		throw new BadMethodCallException("No storage attached.");
	}
	
	/**
	 * @inherit
	 */
	public function getByUUIDOrNull(string $s_uuid) : ?array{
		return null;
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
