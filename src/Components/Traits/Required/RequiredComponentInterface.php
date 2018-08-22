<?php

namespace Reef\Components\Traits\Required;

interface RequiredComponentInterface {
	
	/**
	 * Get the fields necessary for the required field data
	 * @return array List of fields
	 */
	public function getDeclarationFields_required() : array;
	
	/**
	 * Get the locale necessary for the required field
	 * @return array List of locale keys & titles
	 */
	public function getLocale_required() : array;
}
