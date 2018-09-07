<?php

namespace Reef\Assets;

use \Reef\Reef;
use \Reef\Form\Form;

/**
 * FormAssets can be used to only load those assets that are required for
 * displaying the form in question.
 */
class FormAssets extends Assets {
	
	/**
	 * The form to generate obtain the assets for
	 * @type Form
	 */
	private $Form;
	
	/**
	 * Constructor
	 * @param Form $Form The form
	 */
	public function __construct(Form $Form) {
		$this->Form = $Form;
	}
	
	/**
	 * Get the form
	 * @return Form
	 */
	public function getForm() {
		return $this->Form;
	}
	
	/**
	 * @inherit
	 */
	public function getReef() : Reef {
		return $this->Form->getReef();
	}
	
	/**
	 * @inherit
	 */
	protected function getComponents() : array {
		$a_components = [];
		foreach($this->Form->getFields() as $Field) {
			$a_components[] = $Field->getComponent();
		}
		return $a_components;
	}
	
}
