<?php

namespace Reef\Form;

/**
 * Temporary stored form factory
 */
class TempStoredFormFactory extends FormFactory {
	
	/**
	 * @inherit
	 */
	protected function newForm(array $a_definition) : Form {
		if(empty($a_definition['storage_name'])) {
			$a_definition['storage_name'] = 'form_'.\Reef\unique_id();
		}
		
		return new TempStoredForm($this->getReef(), $a_definition);
	}
	
}
