<?php

namespace Reef;

use \Reef\Exception\IOException;
use Symfony\Component\Yaml\Yaml;

class Form {
	
	private $Reef;
	
	private $i_formId;
	private $a_i18n;
	private $a_components = [];
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	public function getFormId() {
		return $this->i_formId;
	}
	
	public function importDeclaration(string $s_filename) {
		if(!file_exists($s_filename) || !is_readable($s_filename)) {
			throw new IOException('Could not find file "'.$s_filename'".');
		}
		
		$a_declaration = Yaml::parseFile($s_filename);
		$Mapper = $this->Reef->getComponentMapper();
		
		foreach($a_declaration['components'] as $s_id => $a_config) {
			$this->a_components[$s_id] = $Mapper->get($a_config);
		}
	}
	
	public function generateDeclaration() : string {
		
	}
	
	public function save() {
		$a_declaration = $this->generateDeclaration();
		
		if($this->i_formId == null) {
			$this->i_formId = $Storage->insert($a_declaration);
		}
		else {
			$Storage->update($this->i_formId, $a_declaration);
		}
	}
	
	
}
