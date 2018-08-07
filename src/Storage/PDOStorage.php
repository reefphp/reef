<?php

namespace Reef\Storage;

use \PDO;
use \Reef\Exception\StorageException;

abstract class PDOStorage implements Storage {
	protected $StorageFactory;
	protected $PDO;
	protected $s_table;
	protected $es_table;
	
	public function __construct(PDOStorageFactory $StorageFactory, PDO $PDO, string $s_table) {
		$this->StorageFactory = $StorageFactory;
		$this->PDO = $PDO;
		$this->s_table = $s_table;
		$this->es_table = static::sanitizeName($this->s_table);
		
		if(!static::table_exists($this->StorageFactory, $this->PDO, $this->s_table)) {
			throw new StorageException("Table ".$this->s_table." does not exist.");
		}
	}
	
	public function getPDO() {
		return $this->PDO;
	}
	
	public function getTableName() {
		return $this->s_table;
	}
	
	abstract public static function startTransaction(\PDO $PDO);
	
	abstract public static function newSavepoint(\PDO $PDO, string $s_savepoint);
	
	abstract public static function rollbackToSavepoint(\PDO $PDO, string $s_savepoint);
	
	abstract public static function commitTransaction(\PDO $PDO);
	
	abstract public static function rollbackTransaction(\PDO $PDO);
	
	abstract public static function createStorage(PDOStorageFactory $StorageFactory, \PDO $PDO, string $s_table) : PDOStorage;
	
	public function renameStorage(string $s_newTableName) {
		$sth = $this->PDO->prepare("ALTER TABLE ".$this->es_table." RENAME TO ".static::sanitizeName($s_newTableName)." ");
		$sth->execute();
		
		if($sth->errorCode() !== '00000') {
			// @codeCoverageIgnoreStart
			throw new StorageException("Could not alter table ".$this->s_table.".");
			// @codeCoverageIgnoreEnd
		}
		
		$this->s_table = $s_newTableName;
		$this->es_table = static::sanitizeName($s_newTableName);
	}
	
	abstract public function addColumns($a_subfields);
	
	abstract public function removeColumns($a_columns);
	
	/**
	 * @inherit
	 */
	public function deleteStorage() : bool {
		$sth = $this->PDO->prepare("
			DROP TABLE ".static::sanitizeName($this->es_table).";
		");
		$sth->execute();
		return $sth->errorCode() === '00000';
	}
	
	/**
	 * @inherit
	 */
	public function list() : array {
		$sth = $this->PDO->prepare("
			SELECT entry_id
			FROM ".$this->es_table."
			ORDER BY entry_id ASC
		");
		$sth->execute();
		$a_rows = $sth->fetchAll(PDO::FETCH_NUM);
		return array_column($a_rows, 0);
	}
	
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
		$this->checkIntegrity($a_data);
		
		$a_keys = $a_values = [];
		
		if($i_entryId !== null) {
			$a_keys[] = 'entry_id';
			$a_values[] = $i_entryId;
		}
		
		foreach($a_data as $s_key => $s_value) {
			$a_keys[] = static::sanitizeName($s_key);
			$a_values[] = $s_value;
		}
		
		$s_keys = implode(', ', $a_keys);
		$s_valueQs = str_repeat('?,', count($a_values)-1).'?';
		
		$sth = $this->PDO->prepare("
			INSERT INTO ".$this->es_table."
			(".$s_keys.")
			VALUES (".$s_valueQs.")
		");
		$sth->execute($a_values);
		return $this->PDO->lastInsertId();
	}
	
	/**
	 * @inherit
	 */
	public function update(int $i_entryId, array $a_data) {
		$this->checkIntegrity($a_data);
		
		$a_sets = $a_values = [];
		
		foreach($a_data as $s_key => $s_value) {
			$a_sets[] = static::sanitizeName($s_key) . " = ? ";
			$a_values[] = $s_value;
		}
		
		$a_values[] = $i_entryId;
		
		$s_sets = implode(', ', $a_sets);
		
		$sth = $this->PDO->prepare("
			UPDATE ".$this->es_table."
			SET ".$s_sets."
			WHERE entry_id = ?
		");
		$sth->execute($a_values);
		return $sth->rowCount();
	}
	
	/**
	 * @inherit
	 */
	public function delete(int $i_entryId) {
		$sth = $this->PDO->prepare("
			DELETE FROM ".$this->es_table."
			WHERE entry_id = ?
		");
		$sth->execute([$i_entryId]);
		return $sth->rowCount();
	}
	
	/**
	 * @inherit
	 */
	public function get(int $i_entryId) : ?array {
		$sth = $this->PDO->prepare("
			SELECT * FROM ".$this->es_table."
			WHERE entry_id = ?
		");
		$sth->execute([$i_entryId]);
		$a_result = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		if(count($a_result) == 0) {
			return null;
		}
		
		$a_result = $a_result[0];
		unset($a_result['entry_id']);
		
		return $a_result;
	}
	
	/**
	 * @inherit
	 */
	public function exists(int $i_entryId) : bool {
		$sth = $this->PDO->prepare("
			SELECT entry_id FROM ".$this->es_table."
			WHERE entry_id = ?
		");
		$sth->execute([$i_entryId]);
		$a_result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return count($a_result) > 0;
	}
	
	/**
	 * @inherit
	 */
	abstract public function next() : int;
	
	protected function checkIntegrity(array $a_data) {
		$a_columns = $this->getColumns();
		
		$a_keys = array_keys($a_data);
		
		if(count($a_columns) != count($a_keys) || !empty(array_diff($a_columns, $a_keys))) {
			throw new StorageException("Invalid data array.");
		}
	}
	
	abstract public function getColumns() : array;
	
	abstract public static function table_exists(PDOStorageFactory $StorageFactory, \PDO $PDO, string $s_tableName) : bool;
	
	public static function sanitizeName($s_name) {
		return preg_replace('/[^a-zA-Z0-9_]/', '', $s_name);
	}
	
}
