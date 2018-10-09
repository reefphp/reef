<?php

namespace Reef\Storage;

use \PDO;
use \Reef\Exception\StorageException;

/**
 * Storage implementation using SQLite
 */
class PDO_SQLite_Storage extends PDOStorage {
	
	/**
	 * Columns information cache
	 * @type array
	 */
	private $a_columnData;
	
	/**
	 * @inherit
	 */
	public static function startTransaction(\PDO $PDO) {
		$PDO->exec("BEGIN TRANSACTION;");
	}
	
	/**
	 * @inherit
	 */
	public static function newSavepoint(\PDO $PDO, string $s_savepoint) {
		$PDO->exec("SAVEPOINT ".static::quoteIdentifier($s_savepoint)." ;");
	}
	
	/**
	 * @inherit
	 */
	public static function rollbackToSavepoint(\PDO $PDO, string $s_savepoint) {
		$PDO->exec("ROLLBACK TRANSACTION TO SAVEPOINT ".static::quoteIdentifier($s_savepoint).";");
	}
	
	/**
	 * @inherit
	 */
	public static function commitTransaction(\PDO $PDO) {
		$PDO->exec("COMMIT TRANSACTION;");
	}
	
	/**
	 * @inherit
	 */
	public static function rollbackTransaction(\PDO $PDO) {
		$PDO->exec("ROLLBACK TRANSACTION;");
	}
	
	/**
	 * @inherit
	 */
	public static function createStorage(PDOStorageFactory $StorageFactory, \PDO $PDO, string $s_table) : PDOStorage {
		$sth = $PDO->prepare("
			CREATE TABLE ".static::quoteIdentifier($s_table)." (
				_entry_id INTEGER PRIMARY KEY AUTOINCREMENT,
				_uuid BLOB UNIQUE
			);
		");
		$sth->execute();
		
		if($sth->errorCode() !== '00000') {
			// @codeCoverageIgnoreStart
			throw new StorageException("Could not create table ".$s_table.".");
			// @codeCoverageIgnoreEnd
		}
		
		return new static($StorageFactory, $PDO, $s_table);
	}
	
	/**
	 * @inherit
	 */
	protected function queryAll(int $i_offset, int $i_num) : \PDOStatement {
		$sth = $this->PDO->prepare("
			SELECT *
			FROM ".$this->qs_table."
			ORDER BY _entry_id ASC
			LIMIT ".$i_num."
			OFFSET ".$i_offset."
		");
		$sth->execute();
		return $sth;
	}
	
	/**
	 * Convert a column structure array into a SQL field specification
	 * @param array $a_subfield The column structure array
	 * @return string The column type specification
	 */
	private function subfield2type($a_subfield) {
		$s_columnType = '';
		
		switch($a_subfield['type']) {
			case Storage::TYPE_TEXT:
				$s_columnType = 'TEXT NULL';
				
				if(isset($a_subfield['default'])) {
					$s_columnType .= ' DEFAULT '.$this->PDO->quote($a_subfield['default']).' ';
				}
			break;
			
			case Storage::TYPE_BOOLEAN:
			case Storage::TYPE_INTEGER:
				$s_columnType = 'NUMERIC NULL';
				
				if(isset($a_subfield['default'])) {
					$s_columnType .= ' DEFAULT '.((int)$a_subfield['default']).'';
				}
			break;
			
			case Storage::TYPE_FLOAT:
				$s_columnType = 'NUMERIC NULL';
				
				if(isset($a_subfield['default'])) {
					$s_columnType .= ' DEFAULT '.((float)$a_subfield['default']).'';
				}
			break;
		}
		
		return $s_columnType;
	}
	
	/**
	 * @inherit
	 */
	public function addColumns($a_subfields) {
		$this->StorageFactory->ensureTransaction(function() use($a_subfields) {
			
			foreach($a_subfields as $s_column => $a_subfield) {
				$sth = $this->PDO->prepare("ALTER TABLE ".$this->qs_table." ADD ".static::quoteIdentifier($s_column)." ".$this->subfield2type($a_subfield)." ");
				$sth->execute();
				
				if($sth->errorCode() !== '00000') {
					// @codeCoverageIgnoreStart
					throw new StorageException("Could not alter table ".$this->s_table.".");
					// @codeCoverageIgnoreEnd
				}
			}
			
			$this->a_columnData = null;
		});
	}
	
	/**
	 * @inherit
	 */
	public function updateColumns($a_subfields) {
		
		$a_update = [];
		
		foreach($a_subfields as $s_columnOld => $a_fieldUpdate) {
			$s_columnNew = $a_fieldUpdate['name'];
			
			$a_columnUpdate = [];
			
			if($s_columnOld != $s_columnNew) {
				$a_columnUpdate['name'] = $s_columnNew;
			}
			
			if(isset($a_fieldUpdate['structureTo']['default']) && (!isset($a_fieldUpdate['structureFrom']['default']) || $a_fieldUpdate['structureFrom']['default'] != $a_fieldUpdate['structureTo']['default'])) {
				$a_columnUpdate['default'] = $a_fieldUpdate['structureTo']['default'];
			}
			
			if(!empty($a_columnUpdate)) {
				$a_update[$s_columnOld] = $a_columnUpdate;
			}
		}
		
		if(!empty($a_update)) {
			$this->migrateColumns([], $a_update);
		}
	}
	
	/**
	 * @inherit
	 */
	public function removeColumns($a_deleteColumns) {
		$this->migrateColumns($a_deleteColumns, []);
	}
	
	/**
	 * Update and delete columns
	 * SQLite does not provide commands to delete or modify columns, hence we will have to migrate
	 * to a new table. As this is required for both deleting and updating columns, we implement these
	 * features here in the same method.
	 * @param string[] $a_deleteColumns The column names to delete
	 * @param array $a_updateColumns The update data, an entry for each column to update with the old
	 * column name as key and the following array as value:
	 *  - 'name'     => The new column name
	 *  - 'default'  => The new default value
	 */
	private function migrateColumns($a_deleteColumns, $a_updateColumns) {
		$this->StorageFactory->ensureTransaction(function() use($a_deleteColumns, $a_updateColumns) {
			
			$a_columnData = $this->getColumnData();
			
			$sth = $this->PDO->prepare("DROP TABLE IF EXISTS __tmp__migration ");
			$sth->execute();
			
			if($sth->errorCode() !== '00000') {
				// @codeCoverageIgnoreStart
				throw new StorageException("Could not drop table __tmp__migration.");
				// @codeCoverageIgnoreEnd
			}
			
			$a_columnsOld = $a_columnsNew = ['_entry_id', '_uuid'];
			
			$s_sql = "CREATE TABLE __tmp__migration (
				_entry_id INTEGER PRIMARY KEY AUTOINCREMENT,
				_uuid BLOB UNIQUE
			";
			foreach($a_columnData as $a_column) {
				if($a_column['name'] == '_entry_id' || $a_column['name'] == '_uuid' || in_array($a_column['name'], $a_deleteColumns)) {
					continue;
				}
				
				$a_columnsOld[] = $a_column['name'];
				
				$s_sql .= ", ";
				if(isset($a_updateColumns[$a_column['name']]['name']) && $a_updateColumns[$a_column['name']]['name'] != $a_column['name']) {
					$s_sql .= $a_updateColumns[$a_column['name']]['name']." ";
					$a_columnsNew[] = $a_updateColumns[$a_column['name']]['name'];
				}
				else {
					$s_sql .= $a_column['name']." ";
					$a_columnsNew[] = $a_column['name'];
				}
				$s_sql .= $a_column['type']." ";
				$s_sql .= (($a_column['notnull'] == 1) ? "NOT NULL" : "NULL")." ";
				if(isset($a_updateColumns[$a_column['name']]) && array_key_exists('default', $a_updateColumns[$a_column['name']])) {
					if($a_updateColumns[$a_column['name']] !== null) {
						$s_sql .= "DEFAULT ".$this->PDO->quote($a_updateColumns[$a_column['name']]['default'])." ";
					}
				}
				else if($a_column['dflt_value'] !== null) {
					$s_sql .= "DEFAULT ".$this->PDO->quote($a_column['dflt_value'])." ";
				}
				$s_sql .= PHP_EOL;
			}
			$s_sql .= ");".PHP_EOL;
			
			$sth = $this->PDO->prepare($s_sql);
			$sth->execute();
			
			if($sth->errorCode() !== '00000') {
				// @codeCoverageIgnoreStart
				throw new StorageException("Could not create table __tmp__migration.");
				// @codeCoverageIgnoreEnd
			}
			
			$sth = $this->PDO->prepare("INSERT INTO __tmp__migration (".implode(', ', $a_columnsNew).") SELECT ".implode(', ', $a_columnsOld)." FROM ".$this->qs_table." ");
			$sth->execute();
			
			if($sth->errorCode() !== '00000') {
				// @codeCoverageIgnoreStart
				throw new StorageException("Could not fill table __tmp__migration.");
				// @codeCoverageIgnoreEnd
			}
			
			$sth = $this->PDO->prepare("DROP TABLE ".$this->qs_table." ");
			$sth->execute();
			
			if($sth->errorCode() !== '00000') {
				// @codeCoverageIgnoreStart
				throw new StorageException("Could not drop table ".$this->s_table.".");
				// @codeCoverageIgnoreEnd
			}
			
			$sth = $this->PDO->prepare("ALTER TABLE __tmp__migration RENAME TO ".$this->qs_table." ");
			$sth->execute();
			
			if($sth->errorCode() !== '00000') {
				// @codeCoverageIgnoreStart
				throw new StorageException("Could not rename table __tmp__migration to ".$this->s_table.".");
				// @codeCoverageIgnoreEnd
			}
			
			$this->a_columnData = null;
		});
	}
	
	/**
	 * @inherit
	 */
	public function next() : int {
		$sth = $this->PDO->prepare("SELECT seq FROM sqlite_sequence WHERE name = ?");
		$sth->execute([$this->s_table]);
		$a_result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return count($a_result) > 0 ? $a_result[0]['seq']+1 : 1;
	}
	
	/**
	 * Get raw column data
	 * @return array
	 */
	private function getColumnData() : array {
		if($this->a_columnData !== null) {
			return $this->a_columnData;
		}
		
		$this->a_columnData = [];
		
		$sth = $this->PDO->prepare("PRAGMA table_info(".$this->qs_table.")");
		$sth->execute();
		
		$this->a_columnData = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		return $this->a_columnData;
	}
	
	/**
	 * @inherit
	 */
	public function getColumns() : array {
		$a_columnData = $this->getColumnData();
		$a_columns = [];
		
		foreach($a_columnData as $a_column) {
			$a_columns[] = $a_column['name'];
		}
		
		return $a_columns;
	}
	
	/**
	 * @inherit
	 */
	public static function table_exists(PDOStorageFactory $StorageFactory, \PDO $PDO, string $s_tableName) : bool {
		$sth = $PDO->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=? COLLATE NOCASE");
		$sth->execute([$s_tableName]);
		$a_rows = $sth->fetchAll(PDO::FETCH_NUM);
		return !empty($a_rows);
	}
	
	/**
	 * @inherit
	 */
	public static function quoteIdentifier($s_name) {
		return '"'.static::sanitizeName($s_name).'"';
	}
	
}
