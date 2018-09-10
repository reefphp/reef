<?php

namespace Reef\Extension;

use \Reef\Reef;
use \Reef\Assets\JSCSSAssetsTrait;

/**
 * Extensions can add general functionality to Reef. Extensions can:
 *  - alter some behaviour in PHP using events
 *  - using such an event, add properties to component configurations
 *  - add template (HTML) code using template hooks
 *  - have their own JS and CSS files
 */
abstract class Extension {
	
	use JSCSSAssetsTrait;
	
	/**
	 * The ExtensionCollection object
	 * @type ExtensionCollection
	 */
	protected $ExtensionCollection;
	
	/**
	 * Set the ExtensionCollection object
	 * @param ExtensionCollection $ExtensionCollection The ExtensionCollection object
	 */
	public function setExtensionCollection(ExtensionCollection $ExtensionCollection) {
		$this->ExtensionCollection = $ExtensionCollection;
	}
	
	/**
	 * Get the ExtensionCollection object
	 * @return ExtensionCollection
	 */
	public function getExtensionCollection() {
		if(empty($this->ExtensionCollection)) {
			throw new \Reef\Exception\LogicException("Extension collection not yet initialized");
		}
		return $this->ExtensionCollection;
	}
	
	/**
	 * Check the setup, may throw an exception if something is not valid
	 */
	public function checkSetup() {
	}
	
	/**
	 * Return the name of this extension
	 * @return string The name
	 */
	abstract public static function getName() : string;
	
	/**
	 * Return the base directory of the current extension
	 * @return string The directory
	 */
	abstract public static function getDir() : string;
	
	/**
	 * Get the Reef object
	 * @return Reef
	 */
	public function getReef() : Reef {
		return $this->getExtensionCollection()->getReef();
	}
	
	/**
	 * Define the assets for this extension
	 * @return array The assets, where key is the asset name and value is the path to the asset
	 */
	public function getAssets() : array {
		return [];
	}
	
	/**
	 * Returns an array of supported layouts. May return null to indicate that
	 * this extension is layout-agnostic
	 * @return ?array
	 */
	abstract public function supportedLayouts() : ?array;
	
	/**
	 * Return an array of extension events to subscribe to
	 * @return string[] Entries with hook name as entry and function name as value
	 */
	public static function getSubscribedEvents() {
		return [];
	}
	
	/**
	 * Get extension template of a template hook
	 * @param string $s_hookName The hook name
	 * @return string The template
	 */
	public function getHookTemplate(string $s_hookName) {
		$Reef = $this->getReef();
		$s_layoutName = $Reef->getSetup()->getLayout()->getName();
		
		$s_templateDir = static::getDir().'/';
		$s_viewfile = 'view/'.$s_layoutName.'/'.$s_hookName.'.mustache';
		
		if(!file_exists($s_templateDir . $s_viewfile)) {
			$s_viewfile = 'view/default/'.$s_hookName.'.mustache';
			
			if(!file_exists($s_templateDir . $s_viewfile)) {
				return '';
			}
		}
		
		$Loader = new \Reef\Mustache\FilesystemLoader($Reef, $s_templateDir);
		return $Loader->load($s_viewfile);
	}
}
