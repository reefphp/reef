<?php

namespace Reef\Components\Traits\Required;

use \Reef\Reef;

trait RequiredComponentTrait {
	
	abstract public function getReef() : Reef;
	
	/**
	 * @inherit
	 */
	public function getDeclarationFields_required() : array {
		$a_fields = [];
		if($this->getReef()->getSetup()->hasComponent('reef:condition')) {
			$a_fields[] = [
				'component' => 'reef:condition',
				'name' => 'required',
				'locales' => $this->getReef()->transMultipleLocales(['title' => 'builder_required'], $this->getReef()->getOption('locales')),
			];
		}
		else {
			$a_fields[] = [
				'component' => 'reef:checkbox',
				'name' => 'required',
				'locales' => $this->getReef()->transMultipleLocales(['box_label' => 'builder_required'], $this->getReef()->getOption('locales')),
			];
		}
		return $a_fields;
	}
	
	/**
	 * @inherit
	 */
	public function getLocale_required() : array {
		return [
			'rf_error_required_empty' => 'rf_error_required_empty_title',
		];
	}
}
