<?php

namespace Reef\Extension;

use \Reef\Reef;

/**
 * Extensions can add general functionality to Reef. Extensions can:
 *  - alter some behaviour in PHP using events
 *  - using such an event, add properties to component configurations
 *  - add template (HTML) code using template hooks
 *  - have their own JS and CSS files
 */
abstract class Extension {
	
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
	 * Define the assets for this extension
	 * @return array The assets, where key is the asset name and value is the path to the asset
	 */
	public function getAssets() : array {
		return [];
	}
	
	/**
	 * Returns an array of supported layouts
	 * @return array
	 */
	abstract public function supportedLayouts() : array;
	
	/**
	 * Returns an array of javascript files required by this extension.
	 * Each file is defined by an array:
	 * [
	 *   type => local or remote
	 *   path => path or url
	 *   view => for which view(s) to load, one of 'form', 'submission', 'builder' or 'all'. Optional, defaults to 'all'
	 *   name => canonical name (required for remote files)
	 *   integrity => Optionally, an integrity value for remote files
	 * ]
	 * @return array The javascript files
	 */
	public function getJS() {
		return [];
	}
	
	/**
	 * Returns an array of CSS files required by this extension.
	 * Each file is defined by an array:
	 * [
	 *   type => local or remote
	 *   path => path or url
	 *   view => for which view(s) to load, one of 'form', 'submission', 'builder' or 'all'. Optional, defaults to 'all'
	 *   name => canonical name (required for remote files)
	 *   integrity => Optionally, an integrity value for remote files
	 * ]
	 * @return array The CSS files
	 */
	public function getCSS() {
		return [];
	}
	
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
		$Reef = $this->getExtensionCollection()->getReef();
		$s_layoutName = $Reef->getSetup()->getLayout()->getName();
		
		$s_templateDir = static::getDir().'/';
		$s_viewfile = 'view/'.$s_layoutName.'/'.$s_hookName.'.mustache';
		
		if(!file_exists($s_templateDir . $s_viewfile)) {
			return '';
		}
		
		$Loader = new \Reef\Mustache\FilesystemLoader($Reef, $s_templateDir);
		return $Loader->load($s_viewfile);
	}
}
