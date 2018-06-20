<?php

namespace Reef;

use \Reef\Storage\Storage;

class Reef {
	
	/**
	 * Place where the forms are stored
	 * @type Storage
	 */
	private $FormStorage;
	
	/**
	 * Mapping from component name to component class path
	 * @type ComponentMapper
	 */
	private $ComponentMapper;
	
	/**
	 * Constructor
	 */
	public function __construct(Storage $FormStorage) {
		$this->FormStorage = $FormStorage;
		$this->ComponentMapper = new ComponentMapper($this);
	}
	
	public function getFormStorage() : Storage {
		return $this->FormStorage;
	}
	
	public function getStorage($a_storageDeclaration) {
		switch(strtolower($a_storageDeclaration['type']??'')) {
			case 'json':
				return new \Reef\Storage\JSONStorage($a_storageDeclaration['path']);
			break;
			
			case 'none':
				return new \Reef\Storage\NoStorage();
			break;
		}
		
		throw new \Exception('Invalid storage.');
	}
	
	public function getForm(int $i_formId) : Form {
		$Form = $this->newForm();
		
		$Form->load($i_formId);
		
		return $Form;
	}
	
	public function newForm() : Form {
		return new Form($this);
		
	}
	
	public function getComponentMapper() : ComponentMapper {
		return $this->ComponentMapper;
	}
	
}
