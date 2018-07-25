<?php

namespace Reef;

class TempForm extends Form {
	
	public function updateDefinition(array $a_definition, array $a_fieldRenames = []) {
		$this->importDefinition($a_definition);
	}
	
	public function checkUpdateDataLoss(array $a_definition, array $a_fieldRenames = []) {
		return [];
	}
	
	public function newSubmission() {
		return new TempSubmission($this);
	}
	
}
