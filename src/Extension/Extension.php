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
	 * Custom layout directories
	 * @type array[]
	 */
	private $a_customLayoutDirs = [];
	
	/**
	 * Cached result of supportedLayouts() (defaults to -1 for 'not initialized')
	 * @type string[]|int
	 */
	private $a_supportedLayouts = -1;
	
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
	 * Initialize the extension
	 * @param \Reef\ReefSetup $ReefSetup The setup
	 */
	public function init($ReefSetup) {
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
	 * Returns an array of natively supported layouts. May return null to indicate that
	 * this extension is layout-agnostic
	 * @return ?array
	 */
	public function nativelySupportedLayouts() : ?array {
		$s_viewDir = static::getDir().'/view/';
		$i_viewDirLen = strlen($s_viewDir);
		$a_layouts = glob($s_viewDir . '*', GLOB_ONLYDIR);
		
		$a_layouts = array_map(function($s_layout) use($i_viewDirLen) {
			return substr($s_layout, $i_viewDirLen);
		}, $a_layouts);
		
		if(in_array('default', $a_layouts)) {
			return null;
		}
		return $a_layouts;
	}
	
	/**
	 * Returns an array of supported layouts. May return null to indicate that
	 * this extension is layout-agnostic
	 * @return ?array
	 */
	public function supportedLayouts() : ?array {
		if($this->a_supportedLayouts !== -1) {
			return $this->a_supportedLayouts;
		}
		
		// Get own supported layouts
		if($this->ExtensionCollection !== null) {
			$a_supportedLayouts = $this->getReef()->cache('supportedLayouts.extension.'.static::getName(), function() {
				return $this->nativelySupportedLayouts();
			});
		}
		else {
			$a_supportedLayouts = $this->nativelySupportedLayouts();
		}
		
		if($a_supportedLayouts === null) {
			$this->a_supportedLayouts = null;
			return null;
		}
		
		// Merge custom supported layouts
		$a_supportedLayouts = array_merge($a_supportedLayouts, array_keys($this->a_customLayoutDirs));
		
		$a_supportedLayouts = array_unique($a_supportedLayouts);
		
		// Cache the result if not still initializing
		if($this->ExtensionCollection !== null && $this->getReef()->getSetup()->isInitialized()) {
			$this->a_supportedLayouts = $a_supportedLayouts;
		}
		
		return $a_supportedLayouts;
	}
	
	/**
	 * Add a layout for this extension
	 * @param string $s_layout The layout
	 * @param string $s_templateDir The template dir
	 * @param string $s_subDir Optionally, the subdirectory within the template dir to use
	 */
	public function addLayout($s_layout, $s_templateDir, $s_subDir = null) {
		if($this->ExtensionCollection !== null && $this->getReef()->getSetup()->isInitialized()) {
			throw new \Reef\Exception\LogicException("Can only add layouts during initialization");
		}
		
		$this->a_customLayoutDirs[$s_layout][] = [
			'template_dir' => $s_templateDir,
			'default_sub_dir' => $s_subDir,
		];
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
		$Reef = $this->getReef();
		$s_layoutName = $Reef->getSetup()->getLayout()->getName();
		
		// Resolve custom layouts
		if(isset($this->a_customLayoutDirs[$s_layoutName])) {
			foreach($this->a_customLayoutDirs[$s_layoutName] as $a_customLayout) {
				if(is_file($a_customLayout['template_dir'] . '/' . ($a_customLayout['default_sub_dir']??'') . '/' . $s_hookName.'.mustache')) {
					$Loader = new \Reef\Mustache\FilesystemLoader($Reef, $a_customLayout['template_dir']);
					return $Loader->load(($a_customLayout['default_sub_dir']??'') . '/' . $s_hookName.'.mustache');
				}
			}
		}
		
		// Resolve own layouts
		$s_templateDir = static::getDir().'/';
		$s_viewfile = 'view/'.$s_layoutName.'/'.$s_hookName.'.mustache';
		
		if(!is_file($s_templateDir . $s_viewfile)) {
			$s_viewfile = 'view/default/'.$s_hookName.'.mustache';
			
			if(!is_file($s_templateDir . $s_viewfile)) {
				return '';
			}
		}
		
		$Loader = new \Reef\Mustache\FilesystemLoader($Reef, $s_templateDir);
		return $Loader->load($s_viewfile);
	}
}
