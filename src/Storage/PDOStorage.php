<?php

namespace Reef\Storage;

use \PDO;
use \Reef\Exception\StorageException;

/**
 * PDOStorage can be used to let Reef store data in a database using PDO
 */
abstract class PDOStorage implements Storage {
	/**
	 * The PDOStorageFactory this object belongs to
	 * @type PDOStorageFactory
	 */
	protected $StorageFactory;
	
	/**
	 * The PDO object
	 * @type PDO
	 */
	protected $PDO;
	
	/**
	 * The table name used by this storage object
	 * @type string
	 */
	protected $s_table;
	
	/**
	 * The escaped table name
	 * @type string
	 */
	protected $qs_table;
	
	/**
	 * Constructor
	 * @param PDOStorageFactory $StorageFactory The PDOStorageFactory this object belongs to
	 * @param PDO $PDO The PDO object
	 * @param string $s_table The table name used by this storage object
	 * @throws StorageException If the table name does not exist
	 */
	public function __construct(PDOStorageFactory $StorageFactory, PDO $PDO, string $s_table) {
		$this->StorageFactory = $StorageFactory;
		$this->PDO = $PDO;
		$this->s_table = $s_table;
		$this->qs_table = static::quoteIdentifier($this->s_table);
		
		if(!static::table_exists($this->StorageFactory, $this->PDO, $this->s_table)) {
			throw new StorageException("Table ".$this->s_table." does not exist.");
		}
	}
	
	/**
	 * Get the used PDO object
	 * @return PDO
	 */
	public function getPDO() {
		return $this->PDO;
	}
	
	/**
	 * Get the used table name
	 * @return string
	 */
	public function getTableName() {
		return $this->s_table;
	}
	
	/**
	 * Start a transaction
	 * @param PDO $PDO The PDO object
	 */
	abstract public static function startTransaction(\PDO $PDO);
	
	/**
	 * Add new savepoint to the current transaction
	 * @param PDO $PDO The PDO object
	 * @param string $s_savepoint The savepoint name
	 */
	abstract public static function newSavepoint(\PDO $PDO, string $s_savepoint);
	
	/**
	 * Rollback to a previously set savepoint
	 * @param PDO $PDO The PDO object
	 * @param string $s_savepoint The savepoint name to rollback to
	 */
	abstract public static function rollbackToSavepoint(\PDO $PDO, string $s_savepoint);
	
	/**
	 * Commit the current transaction
	 * @param PDO $PDO The PDO object
	 */
	abstract public static function commitTransaction(\PDO $PDO);
	
	/**
	 * Rollback the current transaction
	 * @param PDO $PDO The PDO object
	 */
	abstract public static function rollbackTransaction(\PDO $PDO);
	
	/**
	 * Create new storage with the specified name
	 * @param PDOStorageFactory $StorageFactory The calling factory
	 * @param PDO $PDO The PDO object
	 * @param string $s_table The table name to use for the storage
	 * @return PDOStorage The PDOStorage object for the new storage
	 */
	abstract public static function createStorage(PDOStorageFactory $StorageFactory, \PDO $PDO, string $s_table) : PDOStorage;
	
	/**
	 * Rename this storage
	 * @param string $s_newTableName The new table name
	 */
	public function renameStorage(string $s_newTableName) {
		$sth = $this->PDO->prepare("ALTER TABLE ".$this->qs_table." RENAME TO ".static::quoteIdentifier($s_newTableName)." ");
		$sth->execute();
		
		if($sth->errorCode() !== '00000') {
			// @codeCoverageIgnoreStart
			throw new StorageException("Could not alter table ".$this->s_table.".");
			// @codeCoverageIgnoreEnd
		}
		
		$this->s_table = $s_newTableName;
		$this->qs_table = static::quoteIdentifier($s_newTableName);
	}
	
	/**
	 * Add columns to the storage table
	 * @param array $a_subfields Array of column structure arrays, indexed by new column name
	 */
	abstract public function addColumns($a_subfields);
	
	/**
	 * Remove columns from the storage table
	 * @param array $a_subfields Array of update arrays, indexed by old column name. An update array consists of:
	 *  - 'name'          => new column name
	 *  - 'structureFrom' => old column structure array
	 *  - 'structureTo'   => new column structure array
	 */
	abstract public function updateColumns($a_subfields);
	
	/**
	 * Remove columns from the storage table
	 * @param string[] $a_columns The old column names to delete
	 */
	abstract public function removeColumns($a_columns);
	
	/**
	 * @inherit
	 */
	public function deleteStorage() : bool {
		$sth = $this->PDO->prepare("
			DROP TABLE ".static::quoteIdentifier($this->qs_table).";
		");
		$sth->execute();
		return $sth->errorCode() === '00000';
	}
	
	/**
	 * @inherit
	 */
	public function count() : int {
		$sth = $this->PDO->prepare("
			SELECT COUNT(_entry_id) AS num
			FROM ".$this->qs_table."
		");
		$sth->execute();
		$a_rows = $sth->fetchAll(PDO::FETCH_NUM);
		return (int)$a_rows[0][0];
	}
	
	/**
	 * @inherit
	 */
	public function list() : array {
		$sth = $this->PDO->prepare("
			SELECT _entry_id
			FROM ".$this->qs_table."
			ORDER BY _entry_id ASC
		");
		$sth->execute();
		$a_rows = $sth->fetchAll(PDO::FETCH_NUM);
		return array_column($a_rows, 0);
	}
	
	/**
	 * @inherit
	 */
	public function table(int $i_offset = 0, int $i_num = -1) : array {
		$sth = $this->queryAll($i_offset, $i_num);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * @inherit
	 */
	public function generator(int $i_offset = 0, int $i_num = -1) : iterable {
		$sth = $this->queryAll($i_offset, $i_num);
		
		while($a_row = $sth->fetch(PDO::FETCH_ASSOC)) {
			yield $a_row;
		}
	}
	
	/**
	 * Return PDOStatement fetching the requested rows from the table
	 * @param int $i_offset The offset to use
	 * @param int $i_num The number of rows to return
	 * @return \PDOStatement
	 */
	abstract protected function queryAll(int $i_offset, int $i_num) : \PDOStatement;
	
	/**
	 * @inherit
	 */
	public function insert(array $a_data) : int {
		return $this->insertAs(null, $a_data);
	}
	
	/**
	 * @inherit
	 */
	public function insertAs(?int $i_entryId, array $a_data) : int {
		unset($a_data['_entry_id']);
		$a_data['_uuid'] = $a_data['_uuid'] ?? \Reef\unique_id();
		
		$this->checkIntegrity($a_data);
		
		$a_keys = $a_values = [];
		
		$a_keys[] = '_entry_id';
		$a_values[] = $i_entryId; // May be null to get auto increment
		
		foreach($a_data as $s_key => $s_value) {
			$a_keys[] = static::quoteIdentifier($s_key);
			$a_values[] = $s_value;
		}
		
		$s_keys = implode(', ', $a_keys);
		$s_valueQs = str_repeat('?,', count($a_values)-1).'?';
		
		$sth = $this->PDO->prepare("
			INSERT INTO ".$this->qs_table."
			(".$s_keys.")
			VALUES (".$s_valueQs.")
		");
		$sth->execute($a_values);
		return (int)$this->PDO->lastInsertId();
	}
	
	/**
	 * @inherit
	 */
	public function update(int $i_entryId, array $a_data) {
		unset($a_data['_entry_id']);
		unset($a_data['_uuid']);
		
		$this->checkIntegrity($a_data);
		
		$a_sets = $a_values = [];
		
		foreach($a_data as $s_key => $s_value) {
			$a_sets[] = static::quoteIdentifier($s_key) . " = ? ";
			$a_values[] = $s_value;
		}
		
		$a_values[] = $i_entryId;
		
		if(empty($a_sets)) {
			return 1;
		}
		
		$s_sets = implode(', ', $a_sets);
		
		$sth = $this->PDO->prepare("
			UPDATE ".$this->qs_table."
			SET ".$s_sets."
			WHERE _entry_id = ?
		");
		$sth->execute($a_values);
		return $sth->rowCount();
	}
	
	/**
	 * @inherit
	 */
	public function delete(int $i_entryId) {
		$sth = $this->PDO->prepare("
			DELETE FROM ".$this->qs_table."
			WHERE _entry_id = ?
		");
		$sth->execute([$i_entryId]);
		return $sth->rowCount();
	}
	
	/**
	 * @inherit
	 */
	public function get(int $i_entryId) : array {
		$sth = $this->PDO->prepare("
			SELECT * FROM ".$this->qs_table."
			WHERE _entry_id = ?
		");
		$sth->execute([$i_entryId]);
		$a_result = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		if(count($a_result) == 0) {
			throw new StorageException('Could not find entry '.$i_entryId);
		}
		
		$a_result = $a_result[0];
		
		return $a_result;
	}
	
	/**
	 * @inherit
	 */
	public function getOrNull(int $i_entryId) : ?array {
		try {
			return $this->get($i_entryId);
		}
		catch(StorageException $e) {
			return null;
		}
	}
	
	/**
	 * @inherit
	 */
	public function getByUUID(string $s_uuid) : array {
		$sth = $this->PDO->prepare("
			SELECT * FROM ".$this->qs_table."
			WHERE _uuid = ?
		");
		$sth->execute([$s_uuid]);
		$a_result = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		if(count($a_result) == 0) {
			throw new StorageException('Could not find entry with uuid '.$s_uuid);
		}
		
		$a_result = $a_result[0];
		
		return $a_result;
	}
	
	/**
	 * @inherit
	 */
	public function getByUUIDOrNull(string $s_uuid) : ?array {
		try {
			return $this->getByUUID($s_uuid);
		}
		catch(StorageException $e) {
			return null;
		}
	}
	
	/**
	 * @inherit
	 */
	public function exists(int $i_entryId) : bool {
		$sth = $this->PDO->prepare("
			SELECT _entry_id FROM ".$this->qs_table."
			WHERE _entry_id = ?
		");
		$sth->execute([$i_entryId]);
		$a_result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return count($a_result) > 0;
	}
	
	/**
	 * @inherit
	 */
	abstract public function next() : int;
	
	/**
	 * Check that the provided data corresponds to the table structure
	 * @param scalar[] $a_data The data to check
	 * @throws StorageException If the data is invalid
	 */
	protected function checkIntegrity(array $a_data) {
		$a_data['_entry_id'] = $a_data['_uuid'] = null;
		
		$a_columns = $this->getColumns();
		
		$a_keys = array_keys($a_data);
		
		if(count($a_columns) != count($a_keys) || !empty(array_diff($a_columns, $a_keys))) {
			throw new StorageException("Invalid data array.");
		}
	}
	
	/**
	 * Get a list of column names
	 * @return string[] The column names
	 */
	abstract public function getColumns() : array;
	
	/**
	 * Determine whether a table exists
	 * @param PDOStorageFactory $StorageFactory The storage factory
	 * @param PDO $PDO The PDO object
	 * @param string $s_tableName The table name to check
	 * @return bool
	 */
	abstract public static function table_exists(PDOStorageFactory $StorageFactory, \PDO $PDO, string $s_tableName) : bool;
	
	/**
	 * Sanitize a name for use in a query, removing everything but alphanumeric characters and underscores.
	 * @param string $s_name The name to sanitize
	 * @return string
	 */
	public static function sanitizeName($s_name) {
		return preg_replace('/[^a-zA-Z0-9_]/', '', $s_name);
	}
	
	/**
	 * Quote and sanitize an identifier, such as a table name or column name
	 * @param string $s_name The name to quote
	 * @return string
	 */
	abstract public static function quoteIdentifier($s_name);
	
}
