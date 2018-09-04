<?php

namespace Reef\Creator;

/**
 * Functionality for editing a the form definition other than the
 * field properties
 */
class Form extends Context {
	
	/**
	 * @inherit
	 */
	public function getForm() : Form {
		return $this;
	}
	
	/**
	 * Change the storage name
	 * @param string $s_storageName The new storage name
	 * @return $this
	 */
	public function setStorageName($s_storageName) : Form {
		$this->a_definition['storage_name'] = $s_storageName;
		
		return $this;
	}
	
}
