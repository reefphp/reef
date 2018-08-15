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
		
		return $this->Reef->getTempStoredFormFactory()->newForm($a_definition)->toStoredForm();
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
		
		return new StoredForm($this->getReef(), json_decode($a_result['definition'], true), $i_formId);
	}
	
	/**
	 * Create a StoredForm form a TempStoredForm
	 * 
	 * @param TempStoredForm $TempForm The source form
	 * 
	 * @return StoredForm The stored form
	 */
	public function createFromTempStoredForm(TempStoredForm $TempForm) {
		$a_declaration = $TempForm->generateDefinition();
		unset($a_declaration['fields']);
		
		$StoredForm = new StoredForm($this->getReef(), $a_declaration, null);
		
		$Updater = new \Reef\Updater();
		$Updater->update($StoredForm, $TempForm, []);
		
		return $StoredForm;
	}
	
}
