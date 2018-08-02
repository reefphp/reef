<?php

namespace Reef\Storage;

use \PDO;
use \Reef\Exception\RuntimeException;

class PDO_SQLite_Storage extends PDOStorage {
	
	private $a_columnData;
	
	public static function createStorage(\PDO $PDO, string $s_table) : PDOStorage {
		$sth = $PDO->prepare("
			CREATE TABLE ".static::sanitizeName($s_table)." (
				entry_id INTEGER PRIMARY KEY AUTOINCREMENT
			);
		");
		$sth->execute();
		
		if($sth->errorCode() !== '00000') {
			// @codeCoverageIgnoreStart
			throw new RuntimeException("Could not create table ".$s_table.".");
			// @codeCoverageIgnoreEnd
		}
		
		return new static($PDO, $s_table);
	}
	
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
	
	public function addColumns($a_subfields) {
		foreach($a_subfields as $s_column => $a_subfield) {
			$sth = $this->PDO->prepare("ALTER TABLE ".$this->es_table." ADD ".static::sanitizeName($s_column)." ".$this->subfield2type($a_subfield)." ");
			$sth->execute();
			
			if($sth->errorCode() !== '00000') {
				// @codeCoverageIgnoreStart
				throw new RuntimeException("Could not alter table ".$this->s_table.".");
				// @codeCoverageIgnoreEnd
			}
		}
		
		$this->a_columnData = null;
	}
	
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
	
	public function removeColumns($a_deleteColumns) {
		$this->migrateColumns($a_deleteColumns, []);
	}
	
	private function migrateColumns($a_deleteColumns, $a_updateColumns) {
		$a_columnData = $this->getColumnData();
		
		$sth = $this->PDO->prepare("DROP TABLE IF EXISTS __tmp__migration ");
		$sth->execute();
		
		if($sth->errorCode() !== '00000') {
			// @codeCoverageIgnoreStart
			throw new RuntimeException("Could not drop table __tmp__migration.");
			// @codeCoverageIgnoreEnd
		}
		
		$a_columnsOld = $a_columnsNew = [];
		
		$s_sql = "CREATE TABLE __tmp__migration (
			entry_id INTEGER PRIMARY KEY AUTOINCREMENT
		";
		foreach($a_columnData as $a_column) {
			if($a_column['name'] == 'entry_id' || in_array($a_column['name'], $a_deleteColumns)) {
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
			throw new RuntimeException("Could not create table __tmp__migration.");
			// @codeCoverageIgnoreEnd
		}
		
		if(count($a_columnsOld) > 0) {
			$sth = $this->PDO->prepare("INSERT INTO __tmp__migration (".implode(', ', $a_columnsNew).") SELECT ".implode(', ', $a_columnsOld)." FROM ".$this->es_table." ");
			$sth->execute();
			
			if($sth->errorCode() !== '00000') {
				// @codeCoverageIgnoreStart
				throw new RuntimeException("Could not fill table __tmp__migration.");
				// @codeCoverageIgnoreEnd
			}
		}
		
		$sth = $this->PDO->prepare("DROP TABLE ".$this->es_table." ");
		$sth->execute();
		
		if($sth->errorCode() !== '00000') {
			// @codeCoverageIgnoreStart
			throw new RuntimeException("Could not drop table ".$this->s_table.".");
			// @codeCoverageIgnoreEnd
		}
		
		$sth = $this->PDO->prepare("ALTER TABLE __tmp__migration RENAME TO ".$this->es_table." ");
		$sth->execute();
		
		if($sth->errorCode() !== '00000') {
			// @codeCoverageIgnoreStart
			throw new RuntimeException("Could not rename table __tmp__migration to ".$this->s_table.".");
			// @codeCoverageIgnoreEnd
		}
		
		$this->a_columnData = null;
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
	
	private function getColumnData() : array {
		if($this->a_columnData !== null) {
			return $this->a_columnData;
		}
		
		$this->a_columnData = [];
		
		$sth = $this->PDO->prepare("PRAGMA table_info(".$this->es_table.")");
		$sth->execute();
		
		$this->a_columnData = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		return $this->a_columnData;
	}
	
	public function getColumns() : array {
		$a_columnData = $this->getColumnData();
		$a_columns = [];
		
		foreach($a_columnData as $a_column) {
			if($a_column['name'] == 'entry_id') {
				continue;
			}
			
			$a_columns[] = $a_column['name'];
		}
		
		return $a_columns;
	}
	
	public static function table_exists(\PDO $PDO, string $s_tableName) : bool {
		$sth = $PDO->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=? COLLATE NOCASE");
		$sth->execute([$s_tableName]);
		$a_rows = $sth->fetchAll(PDO::FETCH_NUM);
		return !empty($a_rows);
	}
	
	
}
