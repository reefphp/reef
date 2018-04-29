<?php

namespace Reef\Components;

interface Component {
	
	public function __construct(array $a_config);
	
	public static function getDir();
	
	public function getConfig() : array;
	
	public function view_builder() : array;
	
	public function view_form($m_value) : array;
	
	public function validate($m_input, array &$a_errors = null) : bool;
	
	public function store($m_input);
}
