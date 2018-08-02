<?php

namespace Reef;

use Symfony\Component\Yaml\Yaml;
use Reef\Components\Component;
use Reef\Storage\PDOStorage;
use Reef\Exception\RuntimeException;
use Reef\Exception\ValidationException;

class Updater {
	
	const DATALOSS_DEFINITE = 'definite';
	const DATALOSS_POTENTIAL = 'potential';
	const DATALOSS_NO = 'no';
	
	public function __construct() {
	}
	
	private function computeFieldUpdatePlan(StoredForm $Form1, StoredForm $Form2, array $a_fieldRenames) {
		$a_create = $a_update = $a_delete = [];
		
		$a_fields1 = $Form1->getValueFieldsByName();
		$a_fields2 = $Form2->getValueFieldsByName();
		
		foreach($a_fields1 as $s_fieldName1 => $Field1) {
			$s_fieldName2 = null;
			
			if(isset($a_fieldRenames[$s_fieldName1])) {
				$s_fieldName2 = $a_fieldRenames[$s_fieldName1];
				
				if($s_fieldName2 !== null && (!isset($a_fields2[$s_fieldName2]) || get_class($a_fields1[$s_fieldName1]) != get_class($a_fields2[$s_fieldName2]))) {
					throw new RuntimeException("Invalid rename from '".$s_fieldName1."' to '".$s_fieldName2."'.");
				}
			}
			else {
				if(isset($a_fields2[$s_fieldName1])) {
					$s_fieldName2 = $s_fieldName1;
					
					if(get_class($a_fields1[$s_fieldName1]) != get_class($a_fields2[$s_fieldName2])) {
						throw new ValidationException([-1 => ["Cannot delete & add field using the same name '".$s_fieldName1."' in the same update."]]);
					}
				}
			}
			
			if($s_fieldName2 === null) {
				$a_delete[] = $s_fieldName1;
			}
			else {
				$a_update[$s_fieldName1] = $s_fieldName2;
			}
		}
		
		foreach($a_fields2 as $s_fieldName2 => $Field2) {
			if(!in_array($s_fieldName2, $a_update)) {
				$a_create[] = $s_fieldName2;
			}
		}
		
		return [$a_create, $a_update, $a_delete];
	}
	
	private function computeSchemaUpdatePlan(StoredForm $Form1, StoredForm $Form2, array $a_fieldRenames) {
		
		[$a_createFields, $a_updateFields, $a_deleteFields] = $this->computeFieldUpdatePlan($Form1, $Form2, $a_fieldRenames);
		
		$a_createColumns = $a_updateColumns = $a_deleteColumns = [];
		
		$a_fields1 = $Form1->getValueFieldsByName();
		$a_fields2 = $Form2->getValueFieldsByName();
		
		foreach($a_deleteFields as $s_deleteFieldName) {
			$Field = $a_fields1[$s_deleteFieldName];
			$a_deleteColumns = array_merge($a_deleteColumns, $Field->getFlatStructureByColumnName());
		}
		
		foreach($a_createFields as $s_createFieldName) {
			$Field = $a_fields2[$s_createFieldName];
			$a_createColumns = array_merge($a_createColumns, $Field->getFlatStructureByColumnName());
		}
		
		foreach($a_updateFields as $s_fieldName1 => $s_fieldName2) {
			$Field1 = $a_fields1[$s_fieldName1];
			$Field2 = $a_fields2[$s_fieldName2];
			
			$a_structure1 = $Field1->getFlatStructure();
			$a_structure2 = $Field2->getFlatStructure();
			
			$a_structureDelete = $a_structureUpdate = $a_structureCreate = $a_structureUnchanged = [];
			$b_forceUpdate = $Field2->needsSchemaUpdate($Field1);
			
			foreach($a_structure2 as $s_dataFieldName => $a_dataFieldStructure) {
				if(isset($a_structure1[$s_dataFieldName])) {
					if($b_forceUpdate || $s_fieldName1 != $s_fieldName2 || $a_dataFieldStructure != $a_structure1[$s_dataFieldName]) {
						$a_structureUpdate[] = $s_dataFieldName;
					}
					else {
						$a_structureUnchanged[] = $s_dataFieldName;
					}
				}
				else {
					$a_structureCreate[] = $s_dataFieldName;
				}
			}
			
			foreach($a_structure1 as $s_dataFieldName => $a_dataFieldStructure) {
				if(!in_array($s_dataFieldName, $a_structureUpdate) && !in_array($s_dataFieldName, $a_structureUnchanged)) {
					$a_structureDelete[] = $s_dataFieldName;
				}
			}
			
			$a_columnNames1 = $Field1->dataFieldNamesToColumnNames();
			$a_columnNames2 = $Field2->dataFieldNamesToColumnNames();
			
			foreach($a_structureCreate as $s_dataFieldName) {
				$a_createColumns = array_merge($a_createColumns, [
					$a_columnNames2[$s_dataFieldName] => $a_structure2[$s_dataFieldName],
				]);
			}
			
			foreach($a_structureDelete as $s_dataFieldName) {
				$a_deleteColumns = array_merge($a_deleteColumns, [
					$a_columnNames1[$s_dataFieldName] => $a_structure1[$s_dataFieldName],
				]);
			}
			
			foreach($a_structureUpdate as $s_dataFieldName) {
				$a_updateColumns = array_merge($a_updateColumns, [
					$a_columnNames1[$s_dataFieldName] => [
						'fieldNameFrom' => $s_fieldName1,
						'fieldNameTo' => $s_fieldName2,
						'name' => $a_columnNames2[$s_dataFieldName],
						'structureFrom' => $a_structure1[$s_dataFieldName],
						'structureTo' => $a_structure2[$s_dataFieldName],
					],
				]);
			}
		}
		
		return [$a_createColumns, $a_updateColumns, $a_deleteColumns];
	}
	
	private function getColumns(\Reef\Components\Field $Field) {
		// Column names
		$s_name = $Field->getDeclaration()['name'];
		$a_flatStructure = $Field->getFlatStructure();
		
		$a_names = [];
		
		if(count($a_flatStructure) == 1 && \Reef\array_first_key($a_flatStructure) === 0) {
			$a_names[0] = PDOStorage::sanitizeName($s_name);
		}
		else {
			foreach($a_flatStructure as $s_dataFieldName => $a_dataFieldStructure) {
				$a_names[$s_dataFieldName] = PDOStorage::sanitizeName($s_name.'__'.$s_dataFieldName);
			}
		}
		return $a_names;
	}
	
	public function update(StoredForm $Form, StoredForm $newForm, $a_fieldRenames) {
		
		[$a_create, $a_update, $a_delete] = $this->computeSchemaUpdatePlan($Form, $newForm, $a_fieldRenames);
		
		$SubmissionStorage = $Form->getSubmissionStorage();
		$PDO = $SubmissionStorage->getPDO();
		
		$fn_getContentUpdater = function($Field) use($SubmissionStorage) {
			return function(string $s_query, array $a_vars = []) use($Field, $SubmissionStorage) {
				$PDO = $SubmissionStorage->getPDO();
				
				$a_names = [];
				
				// Table name
				$a_names[] = PDOStorage::sanitizeName($SubmissionStorage->getTableName());
				
				// Column names
				$a_names = array_merge($a_names, array_values($this->getColumns($Field)));
				
				// Compute query
				$s_query = vsprintf($s_query, $a_names);
				
				// Execute query
				$sth = $PDO->prepare($s_query);
				$sth->execute($a_vars);
				
				return $sth;
			};
		};
		
		$a_updateFields = [];
		foreach($a_update as $a_fieldUpdate) {
			$a_updateFields[$a_fieldUpdate['fieldNameFrom']] = $a_fieldUpdate['fieldNameTo'];
		}
		
		$a_info = [
			'PDO_DRIVER' => $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME),
		];
		
		$SubmissionStorage->addColumns($a_create);
		
		$a_fields1 = $Form->getValueFieldsByName();
		$a_fields2 = $newForm->getValueFieldsByName();
		
		foreach($a_updateFields as $s_fieldName1 => $s_fieldName2) {
			$a_fields1[$s_fieldName1]->beforeSchemaUpdate(array_merge($a_info, [
				'content_updater' => $fn_getContentUpdater($a_fields1[$s_fieldName1]),
				'new_field' => $a_fields2[$s_fieldName2],
				'old_columns' => $this->getColumns($a_fields1[$s_fieldName1]),
				'new_columns' => $this->getColumns($a_fields2[$s_fieldName2]),
			]));
		}
		
		$SubmissionStorage->updateColumns($a_update);
		
		$SubmissionStorage->removeColumns(array_keys($a_delete));
		
		$Form->setFields($newForm->generateDefinition()['fields']);
		
		$a_fields = $Form->getValueFieldsByName();
		
		foreach($a_updateFields as $s_fieldName1 => $s_fieldName2) {
			$a_fields[$s_fieldName2]->afterSchemaUpdate(array_merge($a_info, [
				'content_updater' => $fn_getContentUpdater($a_fields[$s_fieldName2]),
				'old_columns' => $this->getColumns($a_fields1[$s_fieldName1]),
				'new_columns' => $this->getColumns($a_fields[$s_fieldName2]),
			]));
		}
		
		$Form->save();
		
		if($Form->getStorageName() != $newForm->getStorageName()) {
			$Form->setStorageName($newForm->getStorageName());
		}
		
	}
	
	public function determineUpdateDataLoss(StoredForm $Form, StoredForm $newForm, $a_fieldRenames) {
		
		[$a_createFields, $a_updateFields, $a_deleteFields] = $this->computeFieldUpdatePlan($Form, $newForm, $a_fieldRenames);
		
		$a_loss = [];
		
		$a_fields1 = $Form->getValueFieldsByName();
		$a_fields2 = $newForm->getValueFieldsByName();
		
		foreach($a_deleteFields as $s_deleteFieldName) {
			$Field = $a_fields1[$s_deleteFieldName];
			if($Field->hasValue()) {
				$a_loss[$s_deleteFieldName] = self::DATALOSS_DEFINITE;
			}
		}
		
		foreach($a_updateFields as $s_updateFieldName1 => $s_updateFieldName2) {
			$Field1 = $a_fields1[$s_updateFieldName1];
			$Field2 = $a_fields2[$s_updateFieldName2];
			$a_loss[$s_updateFieldName2] = $Field2->updateDataLoss($Field1);
		}
		
		return $a_loss;
	}
}
