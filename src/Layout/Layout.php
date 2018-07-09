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
	
}
