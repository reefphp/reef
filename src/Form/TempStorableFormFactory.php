<?php

namespace Reef\Form;

/**
 * Temporary stored form factory
 */
class TempStorableFormFactory extends FormFactory {
	
	/**
	 * @inherit
	 */
	protected function newForm(array $a_definition) : Form {
		if(empty($a_definition['storage_name'])) {
			$a_definition['storage_name'] = 'form_'.\Reef\unique_id();
		}
		
		return new TempStorableForm($this->getReef(), $a_definition);
	}
	
}
