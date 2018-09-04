<?php

namespace Reef\Form;

use \Reef\TempSubmission;

/**
 * A TempStoredForm is a Form that is intended to be persisted in the database
 * by the currently running request
 */
class TempStoredForm extends AbstractStoredForm {
	
	/**
	 * @inherit
	 */
	public function updateDefinition(array $a_definition, array $a_fieldRenames = []) {
		$this->setDefinition($a_definition);
	}
	
	/**
	 * @inherit
	 */
	public function checkUpdateDataLoss(array $a_definition, array $a_fieldRenames = []) {
		// Temp forms have no stored submissions, so also no data loss
		// We do import the definition here, to check for any ValidationException
		$Form2 = $this->Reef->newTempForm($a_definition);
		
		return [];
	}
	
	/**
	 * @inherit
	 */
	public function newSubmission() {
		return new TempSubmission($this);
	}
	
	/**
	 * Convert this form to a StoredForm. A new (Stored)Form instance will be generated
	 * representing the stored version of this form.
	 * @return StoredForm
	 */
	public function toStoredForm() : StoredForm {
		return $this->Reef->getStoredFormFactory()->createFromTempStoredForm($this);
	}
	
}
