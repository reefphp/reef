<?php

namespace Reef\Form;

abstract class AbstractStoredForm extends Form {
	
	public function getStorageName() {
		return $this->a_definition['storage_name']??null;
	}
	
	public function setStorageName($s_newStorageName) {
		$this->a_definition['storage_name'] = $s_newStorageName;
	}
	
}
