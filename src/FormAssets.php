<?php

namespace Reef;

use \Reef\Form\Form;

class FormAssets extends Assets {
	
	private $Form;
	
	/**
	 * Constructor
	 */
	public function __construct(Form $Form) {
		$this->Form = $Form;
	}
	
	public function getForm() {
		return $this->Form;
	}
	
	public function getReef() : Reef {
		return $this->Form->getReef();
	}
	
	protected function getComponents() : array {
		$a_components = [];
		foreach($this->Form->getFields() as $Field) {
			$a_components[] = $Field->getComponent();
		}
		return $a_components;
	}
	
}
