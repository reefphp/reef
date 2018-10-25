<?php

namespace Reef\Storage;

use \PDO;
use \Reef\Exception\StorageException;

/**
 * Storage implementation using MySQL
 */
class PDO_MySQL_Storage extends PDOStorage {
	
	/**
	 * Cached column names
	 * @type string[]
	 */
	private $a_columns;
	
	/**
	 * @inherit
	 */
	public static function startTransaction(\PDO $PDO) {
		$PDO->exec("START TRANSACTION;");
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
		$PDO->exec("ROLLBACK TO SAVEPOINT ".static::quoteIdentifier($s_savepoint).";");
	}
	
	/**
	 * @inherit
	 */
	public static function commitTransaction(\PDO $PDO) {
		$PDO->exec("COMMIT;");
	}
	
	/**
	 * @inherit
	 */
	public static function rollbackTransaction(\PDO $PDO) {
		$PDO->exec("ROLLBACK;");
	}
	
	/**
	 * @inherit
	 */
	public static function createStorage(PDOStorageFactory $StorageFactory, \PDO $PDO, string $s_table) : PDOStorage {
		$sth = $PDO->prepare("
			CREATE TABLE ".static::quoteIdentifier($s_table)." (
				_entry_id INT NOT NULL AUTO_INCREMENT,
				_uuid BINARY(32),
				PRIMARY KEY (_entry_id),
				UNIQUE INDEX _uuid (_uuid)
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
		if($i_num <= 0) {
			$i_num = PHP_INT_MAX;
		}
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
		switch($a_subfield['type']) {
			case Storage::TYPE_TEXT:
				$i_limit = (int)($a_subfield['limit']??1000);
				$i_limit = max($i_limit, 1);
				
				if($i_limit <= 100) {
					$s_columnType = 'VARCHAR('.$i_limit.')';
				}
				else if($i_limit <= 16383) {
					$s_columnType = 'TEXT';
				}
				else if($i_limit <= 4194303) {
					$s_columnType = 'MEDIUMTEXT';
				}
				else {
					$s_columnType = 'LONGTEXT';
				}
				
				$s_columnType .= ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
				
				$s_columnType .= ' NULL';
				
				if(isset($a_subfield['default'])) {
					$s_columnType .= ' DEFAULT "'.$this->PDO->quote($a_subfield['default']).'"';
				}
			break;
			
			case Storage::TYPE_BOOLEAN:
				$a_subfield['min'] = 0;
				$a_subfield['max'] = 1;
				
				if(isset($a_subfield['default'])) {
					$a_subfield['default'] = ($a_subfield['default'] > 0) ? 1 : 0;
				}
			
			case Storage::TYPE_INTEGER:
				$i_min = isset($a_subfield['min']) ? (int)$a_subfield['min'] : null;
				$i_max = isset($a_subfield['max']) ? (int)$a_subfield['max'] : null;
				
				if($i_min === null && $i_max === null) {
					$s_columnType = 'INT';
				}
				else {
					if(($i_min === null || $i_min >= 0) && ($i_max === null || $i_max <= 255)) {
						$s_columnType = 'TINYINT UNSIGNED';
					}
					else if(($i_min === null || $i_min >= -128) && ($i_max === null || $i_max <= 127)) {
						$s_columnType = 'TINYINT';
					}
					else if(($i_min === null || $i_min >= 0) && ($i_max === null || $i_max <= 65535)) {
						$s_columnType = 'SMALLINT UNSIGNED';
					}
					else if(($i_min === null || $i_min >= -32768) && ($i_max === null || $i_max <= 32767)) {
						$s_columnType = 'SMALLINT';
					}
					else if(($i_min === null || $i_min >= 0) && ($i_max === null || $i_max <= 16777215)) {
						$s_columnType = 'MEDIUMINT UNSIGNED';
					}
					else if(($i_min === null || $i_min >= -8388608) && ($i_max === null || $i_max <= 8388607)) {
						$s_columnType = 'MEDIUMINT';
					}
					else if(($i_min === null || $i_min >= 0) && ($i_max === null || $i_max <= 4294967295)) {
						$s_columnType = 'INT UNSIGNED';
					}
					else if(($i_min === null || $i_min >= -2147483648) && ($i_max === null || $i_max <= 2147483647)) {
						$s_columnType = 'INT';
					}
					else if($i_min === null || $i_min >= 0) {
						$s_columnType = 'BIGINT UNSIGNED';
					}
					else {
						$s_columnType = 'BIGINT';
					}
				}
				
				$s_columnType .= ' NULL';
				
				if(isset($a_subfield['default'])) {
					$s_columnType .= ' DEFAULT '.((int)$a_subfield['default']).'';
				}
			break;
			
			case Storage::TYPE_FLOAT:
				$s_columnType = 'FLOAT NULL';
				
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
		$this->a_columns = null;
		foreach($a_subfields as $s_column => $a_subfield) {
			$sth = $this->PDO->prepare("ALTER TABLE ".$this->qs_table." ADD ".static::quoteIdentifier($s_column)." ".$this->subfield2type($a_subfield)." ");
			$sth->execute();
			
			if($sth->errorCode() !== '00000') {
				// @codeCoverageIgnoreStart
				throw new StorageException("Could not alter table ".$this->s_table.".");
				// @codeCoverageIgnoreEnd
			}
		}
	}
	
	/**
	 * @inherit
	 */
	public function updateColumns($a_subfields) {
		
		$s_query = "ALTER TABLE ".$this->qs_table." ";
		
		$b_first = true;
		foreach($a_subfields as $s_columnOld => $a_fieldUpdate) {
			$s_columnNew = $a_fieldUpdate['name'];
			
			if(!$b_first) {
				$s_query .= " , ";
			}
			
			$s_query .= " CHANGE ".static::quoteIdentifier($s_columnOld)." ".static::quoteIdentifier($s_columnNew)." ".$this->subfield2type($a_fieldUpdate['structureTo'])." ";
			
			$b_first = false;
		}
		
		$sth = $this->PDO->prepare($s_query);
		$sth->execute();
		$this->a_columns = null;
		
		if($sth->errorCode() !== '00000') {
			// @codeCoverageIgnoreStart
			throw new StorageException("Could not alter table ".$this->s_table.".");
			// @codeCoverageIgnoreEnd
		}
		
	}
	
	/**
	 * @inherit
	 */
	public function removeColumns($a_columns) {
		
		$s_query = "ALTER TABLE ".$this->qs_table." ";
		
		$b_first = true;
		foreach($a_columns as $s_column) {
			if(!$b_first) {
				$s_query .= " , ";
			}
			$s_query .= " DROP ".static::quoteIdentifier($s_column);
			
			$b_first = false;
		}
		
		$sth = $this->PDO->prepare($s_query);
		$sth->execute();
		$this->a_columns = null;
		
		if($sth->errorCode() !== '00000') {
			// @codeCoverageIgnoreStart
			throw new StorageException("Could not alter table ".$this->s_table.".");
			// @codeCoverageIgnoreEnd
		}
	}
	
	/**
	 * @inherit
	 */
	public function next() : int {
		$sth = $this->PDO->prepare("SHOW TABLE STATUS LIKE ?");
		$sth->execute([$this->s_table]);
		return $sth->fetch(PDO::FETCH_ASSOC)['Auto_increment'];
	}
	
	/**
	 * @inherit
	 */
	public function getColumns() : array {
		if($this->a_columns !== null) {
			return $this->a_columns;
		}
		
		$this->a_columns = [];
		
		$sth = $this->PDO->prepare("SELECT * FROM ".$this->qs_table." WHERE 0 LIMIT 0");
		$sth->execute();
		
		$i_columns = $sth->columnCount();
		
		for($i = 0; $i < $i_columns; $i++) {
			$a_column = $sth->getColumnMeta($i);
			
			$this->a_columns[] = $a_column['name'];
		}
		
		return $this->a_columns;
	}
	
	/**
	 * @inherit
	 */
	public static function table_exists(PDOStorageFactory $StorageFactory, \PDO $PDO, string $s_tableName) : bool {
		$sth = $PDO->prepare("SHOW TABLES LIKE ?");
		$sth->execute([$s_tableName]);
		$a_rows = $sth->fetchAll(PDO::FETCH_NUM);
		return !empty($a_rows);
	}
	
	/**
	 * @inherit
	 */
	public static function quoteIdentifier($s_name) {
		return '`'.static::sanitizeName($s_name).'`';
	}
	
}
