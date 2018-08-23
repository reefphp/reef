<?php

namespace Reef\Components\AbstractSingleChoice;

use Reef\Components\FieldValue;

abstract class AbstractSingleChoiceValue extends FieldValue {
	
	protected $s_value;
	
	/**
	 * @inherit
	 */
	public function validate() : bool {
		$this->a_errors = [];
		$s_value = trim($this->s_value);
		
		$a_declaration = $this->Field->getDeclaration();
		
		if($s_value != '' && !in_array($s_value, array_column($a_declaration['options'], 'name'))) {
			$this->a_errors[] = 'Invalid value';
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inherit
	 */
	public function fromDefault() {
		$this->s_value = $this->Field->getDeclaration()['default']??'';
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function isDefault() : bool {
		return ($this->s_value === ($this->Field->getDeclaration()['default']??''));
	}
	
	/**
	 * @inherit
	 */
	public function fromUserInput($s_input) {
		$this->fromStructured($s_input);
	}
	
	/**
	 * @inherit
	 */
	public function fromStructured($s_input) {
		$this->s_value = $s_input;
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toFlat() : array {
		return [
			$this->s_value,
		];
	}
	
	/**
	 * @inherit
	 */
	public function fromFlat(?array $a_flat) {
		$this->s_value = $a_flat[0]??$this->Field->getDeclaration()['default']??'';
		$this->a_errors = null;
	}
	
	/**
	 * @inherit
	 */
	public function toStructured() {
		return $this->s_value;
	}
	
	/**
	 * @inherit
	 */
	public function toOverviewColumns() : array {
		$Reef = $this->Field->getComponent()->getReef();
		$a_langs = $Reef->getOption('locales');
		array_unshift($a_langs, $Reef->getOption('default_locale'));
		$a_langs = array_unique($a_langs);
		
		$s_title = null;
		foreach($this->Field->getDeclaration()['options']??[] as $a_option) {
			if($a_option['name'] != $this->s_value) {
				continue;
			}
			
			foreach($a_langs as $s_lang) {
				if(!empty($a_option['locale'][$s_lang])) {
					$s_title = $a_option['locale'][$s_lang];
					break;
				}
			}
			
			break;
		}
		
		return [
			$s_title
		];
	}
	
	/**
	 * @inherit
	 */
	public function toTemplateVar() {
		return $this->s_value;
	}
}
