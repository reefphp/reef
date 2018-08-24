<?php

namespace Reef\Components\Traits\Hidable;

trait HidableComponentTrait {
	
	abstract public function getReef();
	
	/**
	 * @inherit
	 */
	public function getDeclarationFields_hidable() : array {
		$a_fields = [];
		if($this->getReef()->getSetup()->hasComponent('reef:condition')) {
			$a_fields[] = [
				'component' => 'reef:condition',
				'name' => 'visible',
				'locales' => $this->getReef()->transMultipleLocales(['title' => 'builder_visible'], $this->getReef()->getOption('locales')),
				'default' => true,
			];
		}
		return $a_fields;
	}
}
