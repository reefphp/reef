<?php

namespace Reef\Components;

use Reef\Components\FieldValue;

class NoFieldValue extends FieldValue {
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($s_input) {
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($s_input) {
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
	}
	
	/**
	 * @inherit
	 */
	public function toStructured() {
		return null;
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return null;
	}
}
