<?php

namespace Reef\Form;

/**
 * A StoredForm is a Form that is persisted in the database
 */
class StoredForm extends AbstractStoredForm {
	
	/**
	 * @inherit
	 */
	public function setStorageName($s_newStorageName) {
		if(!empty($this->a_definition['storage_name'])) {
			$this->Reef->getDataStore()->changeSubmissionStorageName($this, $s_newStorageName);
		}
		parent::setStorageName($s_newStorageName);
	}
	
	/**
	 * Update the definition of this form, migrating the data using the Updater class
	 * @inherit
	 */
	public function updateDefinition(array $a_definition, array $a_fieldRenames = []) {
		$Form2 = $this->Reef->newTempStorableForm();
		$Form2->setDefinition($a_definition);
		
		$Updater = new \Reef\Updater();
		$Updater->update($this, $Form2, $a_fieldRenames);
	}
	
	/**
	 * Save this form to the database
	 */
	public function save() {
		$a_definition = $this->getDefinition();
		
		if($this->i_formId == null) {
			$this->i_formId = $this->Reef->getFormStorage()->insert(['definition' => json_encode($a_definition), '_uuid' => $this->getUUID()]);
		}
		else {
			$this->Reef->getFormStorage()->update($this->i_formId, ['definition' => json_encode($a_definition)]);
		}
	}
	
	/**
	 * Delete this form
	 */
	public function delete() {
		$this->Reef->getDataStore()->deleteSubmissionStorageIfExists($this);
		$this->Reef->getDataStore()->getFilesystem()->removeContextDir($this);
		
		if($this->i_formId !== null) {
			$this->Reef->getFormStorage()->delete($this->i_formId);
		}
	}
	
	/**
	 * Duplicate this form into an nonpersistable stored form
	 * 
	 * @return NonpersistableStoredForm
	 */
	public function toNonpersistable() {
		return $this->Reef->getStoredFormFactory()->persistableToNonpersistable($this);
	}
	
}
