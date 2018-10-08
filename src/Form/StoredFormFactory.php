<?php

namespace Reef\Form;

use \Reef\Exception\StorageException;
use \Reef\Exception\ResourceNotFoundException;

/**
 * Stored form factory
 */
class StoredFormFactory extends FormFactory {
	
	/**
	 * @inherit
	 */
	protected function newForm(array $a_definition) : Form {
		if(empty($a_definition['storage_name'])) {
			$a_definition['storage_name'] = 'form_'.\Reef\unique_id();
		}
		
		return $this->Reef->getTempStorableFormFactory()->newForm($a_definition)->toStoredForm();
	}
	
	/**
	 * Load an existing stored form
	 * 
	 * @param int $i_formId The form id to load
	 * 
	 * @return StoredForm The stored form
	 * 
	 * @throws ResourceNotFoundException If form does not exist
	 */
	public function load(int $i_formId) {
		try {
			$a_result = $this->Reef->getFormStorage()->get($i_formId);
		}
		catch(StorageException $e) {
			throw new ResourceNotFoundException('Could not find form with id "'.$i_formId.'"', null, $e);
		}
		
		return new StoredForm($this->getReef(), json_decode($a_result['definition'], true), $i_formId, $a_result['_uuid']);
	}
	
	/**
	 * Load an existing stored form
	 * 
	 * @param int $i_formId The form id to load
	 * 
	 * @return NonpersistableStoredForm The stored form
	 * 
	 * @throws ResourceNotFoundException If form does not exist
	 */
	public function loadNonpersistable(int $i_formId) {
		try {
			$a_result = $this->Reef->getFormStorage()->get($i_formId);
		}
		catch(StorageException $e) {
			throw new ResourceNotFoundException('Could not find form with id "'.$i_formId.'"', null, $e);
		}
		
		return new NonpersistableStoredForm($this->getReef(), json_decode($a_result['definition'], true), $i_formId, $a_result['_uuid']);
	}
	
	/**
	 * Load an existing stored form
	 * 
	 * @param string $s_uuid The form uuid to load
	 * 
	 * @return StoredForm The stored form
	 * 
	 * @throws ResourceNotFoundException If form does not exist
	 */
	public function loadByUUID(string $s_uuid) {
		try {
			$a_result = $this->Reef->getFormStorage()->getByUUID($s_uuid);
		}
		catch(StorageException $e) {
			throw new ResourceNotFoundException('Could not find form with uuid "'.$s_uuid.'"', null, $e);
		}
		
		return new StoredForm($this->getReef(), json_decode($a_result['definition'], true), $a_result['_entry_id'], $a_result['_uuid']);
	}
	
	/**
	 * Load an existing stored form
	 * 
	 * @param string $s_uuid The form uuid to load
	 * 
	 * @return NonpersistableStoredForm The stored form
	 * 
	 * @throws ResourceNotFoundException If form does not exist
	 */
	public function loadNonpersistableByUUID(string $s_uuid) {
		try {
			$a_result = $this->Reef->getFormStorage()->getByUUID($s_uuid);
		}
		catch(StorageException $e) {
			throw new ResourceNotFoundException('Could not find form with uuid "'.$s_uuid.'"', null, $e);
		}
		
		return new NonpersistableStoredForm($this->getReef(), json_decode($a_result['definition'], true), $a_result['_entry_id'], $a_result['_uuid']);
	}
	
	/**
	 * Turn an nonpersistable stored form to a persistable stored form
	 * 
	 * @param StoredForm $SourceForm The source form
	 * 
	 * @return NonpersistableStoredForm The stored form
	 */
	public function persistableToNonpersistable(StoredForm $SourceForm) {
		return new NonpersistableStoredForm($this->getReef(), $SourceForm->getDefinition(), $SourceForm->getFormId(), $SourceForm->getUUID());
	}
	
	/**
	 * Create a StoredForm form a TempStorableForm
	 * 
	 * @param TempStorableForm $TempForm The source form
	 * 
	 * @return StoredForm The stored form
	 */
	public function createFromTempStorableForm(TempStorableForm $TempForm) {
		$a_declaration = $TempForm->getDefinition();
		unset($a_declaration['fields']);
		
		$StoredForm = new StoredForm($this->getReef(), $a_declaration, null, null);
		
		$Updater = new \Reef\Updater();
		$Updater->update($StoredForm, $TempForm, []);
		
		return $StoredForm;
	}
	
}
