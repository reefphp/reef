<?php

namespace Reef\Assets;

use \Reef\Reef;

/**
 * Functionality for JS & CSS assets
 */
trait JSCSSAssetsTrait {
	
	/**
	 * @inherit
	 */
	abstract public static function getDir() : string;
	
	/**
	 * @inherit
	 */
	abstract public function getReef() : Reef;
	
	/**
	 * Returns an array of javascript files required by this element
	 * @see getDefaultJSCSSAssets()
	 * @return array The javascript files
	 */
	public function getJS() {
		return $this->getDefaultJSCSSAssets('js');
	}
	
	/**
	 * Returns an array of CSS files required by this element
	 * @see getDefaultJSCSSAssets()
	 * @return array The CSS files
	 */
	public function getCSS() {
		return $this->getDefaultJSCSSAssets('css');
	}
	
	/**
	 * Returns an array of CSS/JS files present for this element
	 * Each file is defined by an array:
	 * [
	 *   type => local or remote
	 *   path => path or url
	 *   view => for which view(s) to load, one of 'form', 'submission', 'builder' or 'all'. Optional, defaults to 'all'
	 *   name => canonical name (required for remote files)
	 *   integrity => Optionally, an integrity value for remote files
	 * ]
	 * This function searches for files in specific locations, making it possible
	 * to place CSS/JS files on conventional places that do not require any configuration
	 * in the component child class. For JS (and similarly for CSS), the files that
	 * are searched for are:
	 *  - {view}.js
	 *  - js/{view}.js
	 *  - js/{layout}-{view}.js
	 * Here, {view} is one of the views mentioned above, and {layout} is a layout name.
	 * @param string $s_type Either 'css' or 'js'
	 * @return array The CSS/JS files
	 */
	private function getDefaultJSCSSAssets(string $s_type) : array {
		$Reef = $this->getReef();
		$s_dir = rtrim(static::getDir(), '/') . '/';
		
		return $Reef->cache($s_type.'_assets.'.md5($s_dir), function() use($s_type, $Reef, $s_dir) {
			
			$a_files = [];
			
			foreach(['form', 'submission', 'builder', 'all'] as $s_view) {
				// Main file in src/ directory
				$s_path = $s_view . '.' . $s_type;
				if(file_exists($s_dir . $s_path)) {
					$a_files[] = [
						'type' => 'local',
						'path' => $s_path,
						'view' => $s_view,
					];
				}
				
				// Main file in type directory
				$s_path = $s_type . '/' . $s_view . '.' . $s_type;
				if(file_exists($s_dir . $s_path)) {
					$a_files[] = [
						'type' => 'local',
						'path' => $s_path,
						'view' => $s_view,
					];
				}
			}
			
			// Layout files in type directory
			$a_layouts = $Reef->getSetup()->getLayouts();
			foreach(['form', 'submission', 'builder', 'all'] as $s_view) {
				foreach($a_layouts as $s_layoutName => $Layout) {
					$s_path = $s_type . '/' . $s_layoutName . '-' . $s_view . '.' . $s_type;
					if(file_exists($s_dir . $s_path)) {
						$a_files[] = [
							'type' => 'local',
							'path' => $s_path,
							'view' => $s_view,
						];
					}
				}
			}
			
			return $a_files;
		});
	}
	
}
