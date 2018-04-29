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
	 * Mapping from component name to component class path
	 * @type ComponentMapper
	 */
	private $ComponentMapper;
	
	/**
	 * Constructor
	 */
	public function __construct(Storage $Storage) {
		$this->Storage = $Storage;
		$this->ComponentMapper = new ComponentMapper($this);
	}
	
	public function getStorage() : Storage {
		return $this->Storage;
	}
	
	public function getForm(int $i_formId) : Form {
		$Form = $this->newForm();
		
		$Form->importDeclaration($this->Storage->get($i_formId));
		
		return $Form;
	}
	
	public function newForm() : Form {
		return new Form($this);
		
	}
	
	public function getComponentMapper() : ComponentMapper {
		return $this->ComponentMapper;
	}
	
}
