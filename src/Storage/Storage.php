<?php

namespace Reef\Storage;

interface Storage {
	
	const TYPE_TEXT = 'type_text';
	const TYPE_INTEGER = 'type_integer';
	const TYPE_FLOAT = 'type_float';
	const TYPE_BOOLEAN = 'type_boolean';
	
	const TYPES = [
		self::TYPE_TEXT,
		self::TYPE_INTEGER,
		self::TYPE_FLOAT,
		self::TYPE_BOOLEAN,
	];
	
	/**
	 * Delete the entire storage
	 */
	public function deleteStorage();
	
	/**
	 * Return the number of entries
	 * @return int The number of entries
	 */
	public function count() : int;
	
	/**
	 * Return a list of stored entry ids
	 * @return int[] The stored ids
	 */
	public function list() : array;
	
	/**
	 * Return table of raw data
	 * @return string[][] The data
	 */
	public function table(int $i_offset = 0, int $i_num = -1) : array;
	
	/**
	 * Return generator of raw data
	 * @return iterable The data generator
	 */
	public function generator(int $i_offset = 0, int $i_num = -1) : iterable;
	
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
	 * Get an existing entry, or null if it does not exist
	 * @param int $i_entryId The entry id to retrieve
	 * @return array The entry data, or null if it does not exist
	 */
	public function getOrNull(int $i_entryId) : ?array;
	
	/**
	 * Get an existing entry by UUID
	 * @param string $s_uuid The entry uuid to retrieve
	 * @return array The entry data
	 */
	public function getByUUID(string $s_uuid) : array;
	
	/**
	 * Get an existing entry by UUID, or null if it does not exist
	 * @param string $s_uuid The entry uuid to retrieve
	 * @return array The entry data, or null if it does not exist
	 */
	public function getByUUIDOrNull(string $s_uuid) : ?array;
	
	/**
	 * Determine whether an entry exists
	 * @param int $i_entryId The entry id to find
	 * @return boolean True if the specified id corresponds to an existing entry, false otherwise
	 */
	public function exists(int $i_entryId) : bool;
	
	/**
	 * Determine next entry id. This function's value should be taken as an indication, it is not meant to be race-condition safe!
	 * @return int The next entry id
	 */
	public function next() : int;
}
