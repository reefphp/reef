<?php

namespace Reef;

class TempForm extends Form {
	
	public function updateDeclaration(array $a_declaration, array $a_fieldRenames = []) {
		$this->importDeclaration($a_declaration);
	}
	
	public function newSubmission() {
		return new TempSubmission($this);
	}
	
}
