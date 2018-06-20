<?php

namespace Reef\Components;

interface Component {
	
	public function __construct(array $a_config);
	
	/**
	 * Return the base directory of the current component
	 * @return string The directory
	 */
	public static function getDir() : string;
	
	/**
	 * Return the entire configuration array
	 * @return array
	 */
	public function getConfig() : array;
	
	/**
	 * Build template variables for the form builder
	 * @return array The template variables
	 */
	public function view_builder() : array;
	
	/**
	 * Build template variables for the form
	 * @param mixed $m_value The current value of the component
	 * @return array The template variables
	 */
	public function view_form($m_value) : array;
}
