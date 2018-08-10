<?php

namespace Reef\Layout;

interface Layout {
	
	/**
	 * Returns the layout name
	 * @return string The layout name
	 */
	public static function getName() : string;
	
	/**
	 * Get the layout configuration
	 * @return array The layout configuration
	 */
	public function getConfig() : array;
	
	/**
	 * Get a merged layout configuration
	 * @return array The merged layout configuration
	 */
	public function getMergedConfig(array $a_config) : array;
	
	/**
	 * Returns an array of javascript files required by this layout.
	 * Each file is defined by an array:
	 * [
	 *   type => local or remote
	 *   path => path or url
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
	 *   name => canonical name (required for remote files)
	 *   integrity => Optionally, an integrity value for remote files
	 * ]
	 * @return array The CSS files
	 */
	public function getCSS();
	
}
