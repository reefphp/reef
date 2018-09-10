<?php

namespace Reef\Layout;

use \Reef\Reef;
use \Reef\Assets\JSCSSAssetsTrait;

abstract class Layout {
	
	use JSCSSAssetsTrait;
	
	/**
	 * The Reef object
	 * @type Reef
	 */
	protected $Reef;
	
	/**
	 * Set the Reef object
	 * @param Reef $Reef
	 */
	public function setReef(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	/**
	 * Get the Reef object
	 * @return Reef
	 */
	public function getReef() : Reef {
		return $this->Reef;
	}
	
	/**
	 * Returns the layout name
	 * @return string The layout name
	 */
	abstract public static function getName() : string;
	
	/**
	 * Return the base directory of the current layout
	 * @return string The directory
	 */
	abstract public static function getDir() : string;
	
	/**
	 * Get the layout configuration
	 * @return array The layout configuration
	 */
	abstract public function getConfig() : array;
	
	/**
	 * Prepare layout configuration for usage in template
	 * @return array The template vars
	 */
	abstract public function view(array $a_config) : array;
	
}
