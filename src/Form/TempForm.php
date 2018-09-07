<?php

namespace Reef\Form;

use \Reef\Submission\TempSubmission;

/**
 * A TempForm is a Form that is used on-the-fly, and not persisted in the database
 */
class TempForm extends Form {
	
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
	
}
