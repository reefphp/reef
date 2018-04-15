<?php

namespace Reef;

use \Reef\Storage\Storage;

class Reef {
	
	/**
	 * Place where the forms are stored
	 * @type Storage
	 */
	private $Storage;
	
	/**
	 * Constructor
	 */
	public function __construct(Storage $Storage) {
		$this->Storage = $Storage;
	}
	
	public function getStorage() : Storage {
		return $this->Storage;
	}
	
	public function getForm(int $i_formId) : Form {
		
		
	}
	
	public function newForm() : Form {
		return new Form($this);
		
	}
	
}
