<?php

namespace Reef\Form;

use \Reef\Reef;
use \Reef\Exception\ResourceNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * Form factory
 * 
 * Functionality for creating form objects
 */
abstract class FormFactory {
	
	/**
	 * The Reef object this Form belongs to
	 * 
	 * @var Reef
	 */
	protected $Reef;
	
	/**
	 * Constructor
	 * 
	 * @param Reef $Reef The Reef object this factory belongs to
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	/**
	 * Create a new form instance
	 * 
	 * @param array $a_definition The definition array
	 * @return Form The new form instance suitable for the factory type
	 */
	abstract protected function newForm(array $a_definition) : Form;
	
	/**
	 * Get the Reef object this Form belongs to
	 * 
	 * @return Reef
	 */
	public function getReef() {
		return $this->Reef;
	}
	
	/**
	 * Create a form from a form definition file
	 * 
	 * @param string $s_filename The file name
	 * @return Form
	 */
	public function createFromFile(string $s_filename) {
		if(!is_file($s_filename) || !is_readable($s_filename)) {
			throw new ResourceNotFoundException('Could not find file "'.$s_filename.'".');
		}
		
		$a_definition = Yaml::parseFile($s_filename);
		
		return $this->createFromArray($a_definition);
	}
	
	/**
	 * Create a form from a form definition YAML string
	 * 
	 * @param string $s_definition The YAML string holding the definition
	 * @return Form
	 */
	public function createFromString(string $s_definition) {
		$a_definition = Yaml::parse($s_definition);
		
		return $this->createFromArray($a_definition);
	}
	
	/**
	 * Create a form from a form definition array
	 * 
	 * @param array $a_definition The definition array
	 * @return Form
	 */
	public function createFromArray(array $a_definition) {
		$this->Reef->checkDefinition($a_definition);
		return $this->createFromValidatedArray($a_definition);
	}
	
	/**
	 * Create a form from a form definition array, omitting
	 * any validation. Should only be used when confident that the definition
	 * is valid.
	 * 
	 * @param array $a_definition The definition array
	 * @return Form
	 */
	public function createFromValidatedArray(array $a_definition) {
		return $this->newForm($a_definition);
	}
	
}
