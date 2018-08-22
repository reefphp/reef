<?php

namespace Reef\Components\Select;

use Reef\Components\AbstractSingleChoice\AbstractSingleChoiceField;

class SelectField extends AbstractSingleChoiceField {
	
	/**
	 * @inherit
	 */
	public function view_submission($Value, $a_options = []) : array {
		$a_vars = parent::view_submission($Value, $a_options);
		$a_vars['value'] = (string)$Value->toTemplateVar();
		
		$s_selectedName = (string)$Value->toStructured();
		
		$Reef = $this->getComponent()->getReef();
		$a_langs = $Reef->getOption('locales');
		array_unshift($a_langs, $Reef->getOption('default_locale'));
		$a_langs = array_unique($a_langs);
		
		foreach($a_vars['options']??[] as $i => $a_option) {
			
			if($a_option['name'] != $s_selectedName) {
				continue;
			}
			
			$s_title = null;
			foreach($a_langs as $s_lang) {
				if(!empty($a_option['locale'][$s_lang])) {
					$s_title = $a_option['locale'][$s_lang];
					break;
				}
			}
			
			if($s_title !== null) {
				$a_vars['value'] = $s_title;
			}
			break;
		}
		
		return $a_vars;
	}
	
}
