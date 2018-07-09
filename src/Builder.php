<?php

namespace Reef;

use Symfony\Component\Yaml\Yaml;
use Reef\Components\Component;

class Builder {
	
	private $Reef;
	private $a_settings = [
		'submit_action' => null,
	];
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	public function setSetting($s_key, $s_val) {
		if(array_key_exists($s_key, $this->a_settings)) {
			$this->a_settings[$s_key] = $s_val;
		}
	}
	
	public function setSettings($a_settings) {
		foreach($a_settings as $s_key => $s_val) {
			$this->setSetting($s_key, $s_val);
		}
	}
	
	public function generateBuilderHtml(Form $Form) {
		$a_componentMapping = $this->Reef->getComponentMapper()->getMapping();
		$a_categories = [];
		
		$a_locales = ['en_US'];
		
		foreach($a_componentMapping as $s_name => $Component) {
			$a_definition = $Component->getDefinition();
			
			if(!isset($a_categories[$a_definition['category']])) {
				$a_categories[$a_definition['category']] = [
					'category' => $a_definition['category'],
					'components' => [],
				];
			}
			
			$ComponentForm = $this->generateConfigForm($Component);
			$ComponentForm->setIdPfx('__form_idpfx__'.$ComponentForm->getIdPfx());
			$s_form = $ComponentForm->generateFormHtml(null, ['main_var' => 'form_data[config]']);
			
			$a_localeForms = [];
			foreach($a_locales as $s_locale) {
				$LocaleForm = $this->generateLocaleForm($Component, $s_locale);
				$LocaleForm->setIdPfx('__form_idpfx__'.$LocaleForm->getIdPfx());
				
				$a_localeForms[] = [
					'locale' => $s_locale,
					'form' => $LocaleForm->generateFormHtml(null, ['main_var' => 'form_data[locale]['.$s_locale.']']),
				];
			}
			
			$a_categories[$a_definition['category']]['components'][] = [
				'definition' => base64_encode(json_encode($a_definition)),
				'html' => base64_encode($Component->getTemplate($Form->getFormConfig()['layout']['name'])),
				'image' => $a_definition['image'],
				'name' => $a_definition['name'],
				'type' => $a_definition['vendor'].':'.$a_definition['name'],
				'componentForm' => $s_form,
				'localeForms' => $a_localeForms,
			];
			
		}
		$a_categories = array_values($a_categories);
		$a_categories[0]['open_default'] = true;
		
		$a_helpers = [];
		$a_helpers['CSSPRFX'] = $this->Reef->getOption('css_prefix');
		
		$Mustache = new \Mustache_Engine([
			'helpers' => $a_helpers,
		]);
		
		$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader(__DIR__));
		$Template = $Mustache->loadTemplate('view/'.$Form->getFormConfig()['layout']['name'].'/builder.mustache');
		
		$s_html = $Template->render([
			'categories' => $a_categories,
			'formConfig' => base64_encode(json_encode(array_subset($Form->getFormConfig(), ['locale', 'layout']))),
			'formHtml' => $Form->generateFormHtml(null, ['main_var' => 'form_data']),
			'settings' => $this->a_settings,
			'formConfigHtml' => $this->generateFormConfigForm($Form)->generateFormHtml(null, ['main_var' => 'form_config']),
		]);
		
		return $s_html;
	}
	
	
	public function applyBuilderData(Form $Form, array $a_data) {
		$ComponentMapper = $this->Reef->getComponentMapper();
		
		$b_valid = true;
		$a_errors = [];
		
		// Validate form config
		$FormConfigForm = $this->generateFormConfigForm($Form);
		$FormConfigSubmission = $FormConfigForm->newSubmission();
		
		$FormConfigSubmission->fromUserInput($a_data['form_config']);
		
		$b_valid = $FormConfigSubmission->validate() && $b_valid;
		if(!$b_valid) {
			$a_errors[-1] = $FormConfigSubmission->getErrors();
		}
		
		// Validate all fields
		$a_submissions = [];
		
		foreach($a_data['fields'] as $i_pos => $a_field) {
			$Component = $ComponentMapper->getComponent($a_field['component']);
			
			// Validate config
			$ConfigForm = $this->generateConfigForm($Component);
			$ConfigSubmission = $ConfigForm->newSubmission();
			
			$ConfigSubmission->fromUserInput($a_field['config']);
			
			$b_valid = $ConfigSubmission->validate() && $b_valid;
			if(!$b_valid) {
				$a_errors[$i_pos]['config'] = $ConfigSubmission->getErrors();
			}
			
			// Validate locale
			$LocaleForm = $this->generateLocaleForm($Component, 'en_US');
			$LocaleSubmission = $LocaleForm->newSubmission();
			
			$LocaleSubmission->fromUserInput($a_field['locale']);
			
			$b_valid = $LocaleSubmission->validate() && $b_valid;
			if(!$b_valid) {
				$a_errors[$i_pos]['locale'] = $LocaleSubmission->getErrors();
			}
			
			$a_submissions[$i_pos] = [
				'component' => $Component,
				'config' => $ConfigSubmission,
				'locale' => $LocaleSubmission,
			];
		}
		
		if(!$b_valid) {
			return [
				'result' => false,
				'errors' => $a_errors,
			];
		}
		
		$a_fields = [];
		foreach($a_submissions as $i_pos => $a_fieldSubmissions) {
			$Component = $a_fieldSubmissions['component'];
			
			$a_fieldDecl = [
				'component' => $Component::COMPONENT_NAME,
			];
			
			$a_fieldDecl = array_merge($a_fieldDecl, $a_fieldSubmissions['config']->toStructured());
			
			$a_fieldDecl['locale'] = array_merge($a_fieldDecl['locale']??[], $a_fieldSubmissions['locale']->toStructured());
			
			$a_fields[] = $a_fieldDecl;
		}
		
		$Form->mergeConfig($FormConfigSubmission->toStructured());
		$Form->setFields($a_fields);
		
		return [
			'result' => true,
		];
	}
	
	private function generateFormConfigForm(Form $Form) {
		
		$a_declaration = [
			'locale' => [],
			'submissions' => [
				'type' => 'none',
			],
			'layout' => [
				'name' => 'bootstrap4',
				'col_left' => 'col-12',
				'col_right' => 'col-12',
			],
			'fields' => [
				[
					'component' => 'reef:single_line_text',
					'name' => 'storage_name',
					'required' => true,
					'locale' => [
						'title' => 'Form storage name',
					],
					'default' => 'form_'.$Form->getReef()->getFormStorage()->next(),
				]
			],
		];
		
		$ConfigForm = $this->Reef->newForm();
		$ConfigForm->importDeclaration($a_declaration);
		
		return $ConfigForm;
	}
	
	private function generateConfigForm(Component $Component) {
		$a_definition = $Component->getDefinition();
		
		$a_configDeclaration = [
			'locale' => $a_definition['declaration']['locale']??[],
			'submissions' => [
				'type' => 'none',
			],
			'layout' => [
				'name' => 'bootstrap4',
				'col_left' => 'col-12',
				'col_right' => 'col-12',
			],
			'fields' => $a_definition['declaration']['fields']??[],
		];
		
		if($a_definition['category'] !== 'static') {
			array_unshift($a_configDeclaration['fields'], [
				'component' => 'reef:single_line_text',
				'name' => 'name',
				'required' => true,
				'locales' => [
					'en_US' => [
						'title' => 'Field name',
					],
					'nl_NL' => [
						'title' => 'Veldnaam',
					],
				],
			]);
		}
		
		$ComponentForm = $this->Reef->newForm();
		$ComponentForm->importDeclaration($a_configDeclaration);
		
		return $ComponentForm;
	}
	
	private function generateLocaleForm(Component $Component, string $s_locale) {
		if(empty($s_locale)) {
			$s_locale = '-';
		}
		$a_definition = $Component->getDefinition();
		
		$a_localeTitles = $a_definition['locale'];
		$a_locale = $Component->getLocale($s_locale);
		
		$a_fields = [];
		foreach($a_locale as $s_name => $s_val) {
			$a_fields[] = [
				'component' => 'reef:single_line_text',
				'name' => $s_name,
				'locale' => [
					'title' => $a_localeTitles[$s_name]??$s_name
				],
				'default' => $s_val,
			];
		}
		
		$a_localeDeclaration = [
			'locale' => [],
			'submissions' => [
				'type' => 'none',
			],
			'layout' => [
				'name' => 'bootstrap4',
				'col_left' => 'col-12',
				'col_right' => 'col-12',
			],
			'fields' => $a_fields,
		];
		
		$LocaleForm = $this->Reef->newForm();
		$LocaleForm->importDeclaration($a_localeDeclaration);
		
		return $LocaleForm;
	}
}
