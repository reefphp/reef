<?php

namespace Reef\Storage;

interface Storage {
	
	/**
	 * Return a list of stored entry ids
	 * @return array<int> The stored ids
	 */
	public function list() : array;
	
	/**
	 * Insert a new entry
	 * @param array $a_data The new data to save
	 * @return int The new entry id
	 */
	public function insert(array $a_data) : int;
	
	/**
	 * Update an existing entry
	 * @param int $i_entryId The entry id to update
	 * @param array $a_data The new data to save
	 */
	public function update(int $i_entryId, array $a_data);
	
	/**
	 * Delete an existing entry
	 * @param int $i_entryId The entry id to delete
	 */
	public function delete(int $i_entryId);
	
	/**
	 * Get an existing entry
	 * @param int $i_entryId The entry id to retrieve
	 * @return array The entry data
	 */
	public function get(int $i_entryId) : array;
	
	/**
	 * Determine whether an entry exists
	 * @param int $i_entryId The entry id to find
	 * @return boolean True if the specified id corresponds to an existing entry, false otherwise
	 */
	public function exists(int $i_entryId) : bool;
}
