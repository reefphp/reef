<?php

namespace Reef\Form;

/**
 * An AbstractStorableForm is a Form that is persisted in the database, or is
 * intended to be persisted by the currently running request
 */
abstract class AbstractStorableForm extends Form {
	
	/**
	 * Get the storage name
	 * @return ?string The storage name, or null if it is not set
	 */
	public function getStorageName() {
		return $this->a_definition['storage_name']??null;
	}
	
	/**
	 * Set the storage name
	 * @param string $s_newStorageName The new storage name
	 */
	public function setStorageName($s_newStorageName) {
		$this->a_definition['storage_name'] = $s_newStorageName;
	}
	
}
