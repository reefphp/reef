<?php

namespace Reef\Creator;

class Form extends Context {
	
	public function getForm() : Form {
		return $this;
	}
	
	public function setStorageName($s_storageName) : Form {
		$this->a_definition['storage_name'] = $s_storageName;
		
		return $this;
	}
	
}
