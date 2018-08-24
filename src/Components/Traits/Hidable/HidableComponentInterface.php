<?php

namespace Reef\Components\Traits\Hidable;

interface HidableComponentInterface {
	
	/**
	 * Get the fields necessary for the hidable field data
	 * @return array List of fields
	 */
	public function getDeclarationFields_hidable() : array;
}
