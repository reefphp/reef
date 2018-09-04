<?php

namespace Reef;

use \Symfony\Component\Yaml\Yaml;
use \Reef\Form\StoredForm;
use \Reef\Form\TempStoredForm;
use \Reef\Components\Component;
use \Reef\Storage\PDOStorage;
use \Reef\Exception\RuntimeException;
use \Reef\Exception\ValidationException;

/**
 * The updater facilitates functionality for migrating forms from one definition to another. As in general
 * data loss is very well possible, a systematic procedure is required to migrate a form, which is what is
 * provided here.
 */
class Updater {
	
	const DATALOSS_DEFINITE = 'definite';
	const DATALOSS_POTENTIAL = 'potential';
	const DATALOSS_NO = 'no';
	
	/**
	 * Constructor
	 */
	public function __construct() {
	}
	
	/**
	 * Compute a field update plan: Given a form $Form1 and a new definition $Form2, with optionally some field renames $a_fieldRenames,
	 * what needs to happen to migrate $Form1 to the definition of $Form2? This is computed into a series of `created`, `updated` and
	 * `deleted` fields.
	 * @param StoredForm $Form1 The form that is being migrated
	 * @param TempStoredForm $Form2 The new form definition
	 * @param string[] $a_fieldRenames Mapping from old field names to new field names, where applicable
	 * @return array Field update plan, consisting of:
	 *  - string[] $a_create: list of created field names (name as in $Form2)
	 *  - string[] $a_update: list of fields present in both $Form1 and $Form2, its name in $Form1 as key and its name in $Form2 as value
	 *  - string[] $a_delete: list of deleted field names (name as in $Form1)
	 */
	private function computeFieldUpdatePlan(StoredForm $Form1, TempStoredForm $Form2, array $a_fieldRenames) {
		$a_create = $a_update = $a_delete = [];
		
		$a_fields1 = $Form1->getValueFieldsByName();
		$a_fields2 = $Form2->getValueFieldsByName();
		
		foreach($a_fields1 as $s_fieldName1 => $Field1) {
			$s_fieldName2 = null;
			
			if(isset($a_fieldRenames[$s_fieldName1])) {
				// The user has renamed the field
				$s_fieldName2 = $a_fieldRenames[$s_fieldName1];
				
				// We should make sure the real name also really exists in the new form, and that it is the same component (otherwise things will get really complicated)
				if($s_fieldName2 !== null && (!isset($a_fields2[$s_fieldName2]) || get_class($a_fields1[$s_fieldName1]) != get_class($a_fields2[$s_fieldName2]))) {
					throw new RuntimeException("Invalid rename from '".$s_fieldName1."' to '".$s_fieldName2."'.");
				}
			}
			else {
				// No registered rename
				if(isset($a_fields2[$s_fieldName1])) {
					// The name of this field has not changed
					$s_fieldName2 = $s_fieldName1;
					
					// Also in this case, the component should remain the same
					if(get_class($a_fields1[$s_fieldName1]) != get_class($a_fields2[$s_fieldName2])) {
						throw new ValidationException([-1 => ["Cannot delete & add field using the same name '".$s_fieldName1."' in the same update."]]);
					}
				}
				// Else, the field is deleted
			}
			
			if($s_fieldName2 === null) {
				// No new name is found, so this field is deleted
				$a_delete[] = $s_fieldName1;
			}
			else {
				// A new name is found, so this field should be updated into the new form (or nothing should be done at all, but we don't know that yet here)
				$a_update[$s_fieldName1] = $s_fieldName2;
			}
		}
		
		// Any fields in $Form2 that are not in the update array, are new fields
		foreach($a_fields2 as $s_fieldName2 => $Field2) {
			if(!in_array($s_fieldName2, $a_update)) {
				$a_create[] = $s_fieldName2;
			}
		}
		
		return [$a_create, $a_update, $a_delete];
	}
	
	/**
	 * Compute a schema update plan: Given a form $Form1 and a new definition $Form2, with optionally some field renames $a_fieldRenames,
	 * computeFieldUpdatePlan() computes which fields are should be created/updated/deleted. Building upon that, this function determines
	 * which columns/datafields should be created/updated/deleted. Of course, created/deleted fields lead to created/deleted columns only,
	 * but updated fields may lead to created, updated as well as deleted columns. This is determined here.
	 * Note: a column name is defined to be (more or less) the field name + the datafield name. See getColumns() below
	 * @param StoredForm $Form1 The form that is being migrated
	 * @param TempStoredForm $Form2 The new form definition
	 * @param string[] $a_fieldRenames Mapping from old field names to new field names, where applicable
	 * @return array Schema update plan, consisting of the return values of computeFieldUpdatePlan() and:
	 *  - array $a_createColumns: list of created columns, the column name in $Form2 as key and structure array as value
	 *  - array $a_updateColumns: list of updated columns, the column name in $Form1 as key and the following array as value:
	 *     - fieldNameFrom    string   The old field name
	 *     - fieldNameTo      string   The new field name
	 *     - name             string   The column name in $Form2
	 *     - structureFrom    array    The old structure array
	 *     - structureTo      array    The new structure array
	 *  - array $a_deleteColumns: list of deleted columns, the column name in $Form1 as key and structure array as value
	 */
	private function computeSchemaUpdatePlan(StoredForm $Form1, TempStoredForm $Form2, array $a_fieldRenames) {
		// Fetch field update plan
		[$a_createFields, $a_updateFields, $a_deleteFields] = $this->computeFieldUpdatePlan($Form1, $Form2, $a_fieldRenames);
		
		$a_createColumns = $a_updateColumns = $a_deleteColumns = [];
		
		$a_fields1 = $Form1->getValueFieldsByName();
		$a_fields2 = $Form2->getValueFieldsByName();
		
		// First easy case: all columns of all deleted fields should be deleted
		foreach($a_deleteFields as $s_deleteFieldName) {
			$Field = $a_fields1[$s_deleteFieldName];
			$a_deleteColumns = array_merge($a_deleteColumns, $Field->getFlatStructureByColumnName());
		}
		
		// Second easy case: all columns of all created fields should be created
		foreach($a_createFields as $s_createFieldName) {
			$Field = $a_fields2[$s_createFieldName];
			$a_createColumns = array_merge($a_createColumns, $Field->getFlatStructureByColumnName());
		}
		
		// Hard case: updated columns may lead to created, deleted, updated columns, or no action at all
		foreach($a_updateFields as $s_fieldName1 => $s_fieldName2) {
			$Field1 = $a_fields1[$s_fieldName1];
			$Field2 = $a_fields2[$s_fieldName2];
			
			$a_structure1 = $Field1->getFlatStructure();
			$a_structure2 = $Field2->getFlatStructure();
			
			$a_structureDelete = $a_structureUpdate = $a_structureCreate = $a_structureUnchanged = [];
			
			// We compare the old flat structure and new flat structure. The component may request a
			// forced update, as determined here
			$b_forceUpdate = $Field2->needsSchemaUpdate($Field1);
			
			// For each column in the database of $Form2
			foreach($a_structure2 as $s_dataFieldName => $a_dataFieldStructure) {
				if(isset($a_structure1[$s_dataFieldName])) {
					// $Form1 contains the same column
					if($b_forceUpdate || $s_fieldName1 != $s_fieldName2 || $a_dataFieldStructure != $a_structure1[$s_dataFieldName]) {
						// The component requested an update, the field name has changed, or the structure of the column has changed.
						// Hence: we need to update
						$a_structureUpdate[] = $s_dataFieldName;
					}
					else {
						// Nothing changed
						$a_structureUnchanged[] = $s_dataFieldName;
					}
				}
				else {
					// $Form1 does not contain this column, so it is created
					$a_structureCreate[] = $s_dataFieldName;
				}
			}
			
			// Any column that is present in $Form1 but not in $Form2, should be deleted
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
		
		return [$a_createFields, $a_updateFields, $a_deleteFields, $a_createColumns, $a_updateColumns, $a_deleteColumns];
	}
	
	/**
	 * Get the column names of a field. Turns data field names into column names
	 * @param Field $Field The field
	 * @return string[] $a_names The column names
	 */
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
	
	/**
	 * Migrate a form, updating it from one definition to another
	 * @param StoredForm $Form The form to migrate
	 * @param TempStoredForm $newForm The new form definition to use
	 * @param string[] $a_fieldRenames Mapping from old field names to new field names, where applicable
	 */
	public function update(StoredForm $Form, TempStoredForm $newForm, $a_fieldRenames) {
		
		[$a_createFields, $a_updateFields, $a_deleteFields, $a_createColumns, $a_updateColumns, $a_deleteColumns] = $this->computeSchemaUpdatePlan($Form, $newForm, $a_fieldRenames);
		
		if(empty($Form->getStorageName())) {
			$Form->setStorageName($newForm->getStorageName());
		}
		
		if($Form->getFormId() === null || $newForm->getStorageName() != $Form->getStorageName()) {
			if($Form->getReef()->getDataStore()->hasSubmissionStorage($newForm->getStorageName())) {
				throw new ValidationException([-1 => ["Storage name '".$newForm->getStorageName()."' is already in use"]]);
			}
		}
		
		$Form->createSubmissionStorageIfNotExists();
		
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
		
		$a_info = [
			'PDO_DRIVER' => $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME),
		];
		
		$Form->getReef()->getDataStore()->ensureTransaction(function() use(
			$SubmissionStorage,
			$Form,
			$newForm,
			$a_info,
			$a_createColumns,
			$a_updateColumns,
			$a_deleteColumns,
			$a_updateFields,
			$a_deleteFields,
			$fn_getContentUpdater
			) {
			
			$SubmissionStorage->addColumns($a_createColumns);
			
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
			
			foreach($a_deleteFields as $s_fieldName1) {
				$a_fields1[$s_fieldName1]->beforeDelete(array_merge($a_info, [
					'content_updater' => $fn_getContentUpdater($a_fields1[$s_fieldName1]),
					'columns' => $this->getColumns($a_fields1[$s_fieldName1]),
				]));
			}
			
			$SubmissionStorage->updateColumns($a_updateColumns);
			
			$SubmissionStorage->removeColumns(array_keys($a_deleteColumns));
			
			$Form->setFields($newForm->getDefinition()['fields']);
			
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
				$Form->save();
			}
			
		});
		
	}
	
	/**
	 * Determine the dataloss that a migration will induce
	 * @param StoredForm $Form The form to migrate
	 * @param TempStoredForm $newForm The new form definition to use
	 * @param string[] $a_fieldRenames Mapping from old field names to new field names, where applicable
	 * @return string[] The data loss, with field names as key and one of self::DATALOSS_* as value
	 */
	public function determineUpdateDataLoss(StoredForm $Form, TempStoredForm $newForm, $a_fieldRenames) {
		
		[$a_createFields, $a_updateFields, $a_deleteFields] = $this->computeFieldUpdatePlan($Form, $newForm, $a_fieldRenames);
		
		$a_loss = [];
		
		$a_fields1 = $Form->getValueFieldsByName();
		$a_fields2 = $newForm->getValueFieldsByName();
		
		// Deleted fields are lost definitely
		foreach($a_deleteFields as $s_deleteFieldName) {
			$Field = $a_fields1[$s_deleteFieldName];
			if($Field->hasValue()) {
				$a_loss[$s_deleteFieldName] = self::DATALOSS_DEFINITE;
			}
		}
		
		// The data loss of updated fields is determined by the fields themselves
		foreach($a_updateFields as $s_updateFieldName1 => $s_updateFieldName2) {
			$Field1 = $a_fields1[$s_updateFieldName1];
			$Field2 = $a_fields2[$s_updateFieldName2];
			$a_loss[$s_updateFieldName2] = $Field2->updateDataLoss($Field1);
		}
		
		return $a_loss;
	}
}
