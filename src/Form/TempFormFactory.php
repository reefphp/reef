<?php

namespace Reef\Form;

/**
 * Temp form factory
 */
class TempFormFactory extends FormFactory {
	
	/**
	 * @inherit
	 */
	protected function newForm(array $a_definition) : Form {
		return new TempForm($this->getReef(), $a_definition);
	}
	
}
