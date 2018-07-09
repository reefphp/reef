<?php

namespace Reef;

class ReefAssets extends Assets {
	
	private $Reef;
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	public function getReef() : Reef {
		return $this->Reef;
	}
	
	protected function getComponents() : array {
		return array_values($this->Reef->getSetup()->getComponentMapping());
	}
	
}
