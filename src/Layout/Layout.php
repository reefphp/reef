<?php

namespace Reef\Layout;

interface Layout {
	
	/**
	 * Returns the layout name
	 * @return string The layout name
	 */
	public static function getName() : string;
	
	/**
	 * Return the base directory of the current layout
	 * @return string The directory
	 */
	public static function getDir() : string;
	
	/**
	 * Get the layout configuration
	 * @return array The layout configuration
	 */
	public function getConfig() : array;
	
	/**
	 * Prepare layout configuration for usage in template
	 * @return array The template vars
	 */
	public function view(array $a_config) : array;
	
	/**
	 * Returns an array of javascript files required by this layout.
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
	public function getJS();
	
	/**
	 * Returns an array of CSS files required by this layout.
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
	public function getCSS();
	
}
