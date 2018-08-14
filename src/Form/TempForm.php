<?php

namespace Reef\Form;

use \Reef\TempSubmission;

class TempForm extends Form {
	
	public function updateDefinition(array $a_definition, array $a_fieldRenames = []) {
		$this->setDefinition($a_definition);
	}
	
	public function checkUpdateDataLoss(array $a_definition, array $a_fieldRenames = []) {
		// Temp forms have no stored submissions, so also no data loss
		// We do import the definition here, to check for any ValidationException
		$Form2 = $this->Reef->newTempForm($a_definition);
		
		return [];
	}
	
	public function newSubmission() {
		return new TempSubmission($this);
	}
	
}
