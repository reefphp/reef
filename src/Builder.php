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
		$ReefAssets = $this->Reef->getReefAssets();
		$ReefAssets->addLocalCSS('assets/builder.css');
		$ReefAssets->addLocalJS('assets/builder.js');
		
		$Layout = $this->Reef->getSetup()->getLayout();
		$a_componentMapping = $this->Reef->getSetup()->getComponentMapping();
		$a_categories = [];
		
		$a_locales = $this->Reef->getOption('locales');
		
		foreach($a_componentMapping as $s_name => $Component) {
			$a_configuration = $Component->getConfiguration();
			
			if(!isset($a_categories[$a_configuration['category']])) {
				$a_categories[$a_configuration['category']] = [
					'category' => $a_configuration['category'],
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
			
			$a_categories[$a_configuration['category']]['components'][] = [
				'configuration' => base64_encode(json_encode($a_configuration)),
				'html' => base64_encode($Component->getTemplate($Layout->getName())),
				'image' => $a_configuration['image'],
				'name' => $a_configuration['name'],
				'type' => $a_configuration['vendor'].':'.$a_configuration['name'],
				'componentForm' => $s_form,
				'localeForms' => $a_localeForms,
			];
			
		}
		$a_categories = array_values($a_categories);
		$a_categories[0]['open_default'] = true;
		$a_fields = [];
		foreach($Form->getFields() as $Field) {
			$s_declaration = base64_encode(json_encode($Field->getDeclaration()));
			
			$ComponentForm = $this->generateConfigForm($Field->getComponent());
			$ComponentSubmission = $ComponentForm->newSubmission();
			$a_declaration = $Field->getDeclaration();
			if(isset($a_declaration['name'])) {
				$a_declaration['old_name'] = $a_declaration['name'];
			}
			$ComponentSubmission->fromUserInput($a_declaration);
			$s_form = $ComponentForm->generateFormHtml($ComponentSubmission, ['main_var' => 'form_data[config]']);
			
			$a_localeForms = [];
			foreach($a_locales as $s_locale) {
				$LocaleForm = $this->generateLocaleForm($Field->getComponent(), $s_locale);
				$LocaleSubmission = $LocaleForm->newSubmission();
				$LocaleSubmission->fromUserInput($Field->getDeclaration()['locales'][$s_locale] ?? $Field->getDeclaration()['locale'] ?? []);
				
				$a_localeForms[] = [
					'locale' => $s_locale,
					'form' => $LocaleForm->generateFormHtml($LocaleSubmission, ['main_var' => 'form_data[locale]['.$s_locale.']']),
				];
			}
			
			$a_fields[] = [
				'declaration' => $s_declaration,
				'type' => $Field->getComponent()::COMPONENT_NAME,
				'componentForm' => $s_form,
				'localeForms' => $a_localeForms,
			];
		}
		
		// Form definition
		$DefinitionForm = $this->generateDefinitionForm($Form);
		$DefinitionSubmission = $DefinitionForm->newSubmission();
		$DefinitionSubmission->fromUserInput([
			'storage_name' => ($Form instanceof StoredForm) ? $Form->getStorageName() : 'temporary_form',
		]);
		
		$EmptyForm = clone $Form;
		$EmptyForm->setFields([]);
		$s_formHtml = $EmptyForm->generateFormHtml();
		
		$a_helpers = [];
		$a_helpers['CSSPRFX'] = $this->Reef->getOption('css_prefix');
		
		$Mustache = new \Mustache_Engine([
			'helpers' => $a_helpers,
		]);
		
		$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader(__DIR__));
		$Template = $Mustache->loadTemplate('view/'.$Layout->getName().'/builder.mustache');
		
		$s_html = $Template->render([
			'categories' => $a_categories,
			'formConfig' => base64_encode(json_encode(array_merge(
				array_subset($Form->getDefinition(), ['locale']),
				[
					'layout_name' => $Layout->getName(),
					'layout' => $Layout->getConfig(),
				]
			))),
			'formHtml' => $s_formHtml,
			'settings' => $this->a_settings,
			'fields' => $a_fields,
			'definitionForm' => $DefinitionForm->generateFormHtml($DefinitionSubmission, ['main_var' => 'definition']),
			'form_id' => ($Form instanceof StoredForm) ? $Form->getFormId() : -1,
			'multipleLocales' => (count($a_locales) > 1),
		]);
		
		return $s_html;
	}
	
	
	public function applyBuilderData(Form $Form, array $a_data) {
		$Setup = $this->Reef->getSetup();
		$a_locales = $this->Reef->getOption('locales');
		
		$b_valid = true;
		$a_errors = [];
		
		// Validate form definition
		$DefinitionForm = $this->generateDefinitionForm($Form);
		$DefinitionSubmission = $DefinitionForm->newSubmission();
		
		$DefinitionSubmission->fromUserInput($a_data['definition']);
		
		$b_valid = $DefinitionSubmission->validate() && $b_valid;
		if(!$b_valid) {
			$a_errors[-1] = $DefinitionSubmission->getErrors();
		}
		
		// Validate all fields
		$a_submissions = [];
		
		foreach($a_data['fields'] as $i_pos => $a_field) {
			$Component = $Setup->getComponent($a_field['component']);
			
			// Validate config
			$ConfigForm = $this->generateConfigForm($Component);
			$ConfigSubmission = $ConfigForm->newSubmission();
			
			$ConfigSubmission->fromUserInput($a_field['config']);
			
			$b_valid = $ConfigSubmission->validate() && $b_valid;
			if(!$b_valid) {
				$a_errors[$i_pos]['config'] = $ConfigSubmission->getErrors();
			}
			
			// Validate locale
			$a_localeSubmissions = [];
			foreach($a_locales as $s_locale) {
				$LocaleForm = $this->generateLocaleForm($Component, $s_locale);
				$LocaleSubmission = $LocaleForm->newSubmission();
				$LocaleSubmission->fromUserInput($a_field['locale'][$s_locale]??[]);
				
				$b_valid = $LocaleSubmission->validate() && $b_valid;
				if(!$b_valid) {
					$a_errors[$i_pos]['locale'][$s_locale] = $LocaleSubmission->getErrors();
				}
				$a_localeSubmissions[$s_locale] = $LocaleSubmission;
			}
			
			$a_submissions[$i_pos] = [
				'component' => $Component,
				'config' => $ConfigSubmission,
				'locales' => $a_localeSubmissions,
			];
		}
		
		if(!$b_valid) {
			return [
				'result' => false,
				'errors' => $a_errors,
			];
		}
		
		$a_fields = $a_fieldRenames = [];
		foreach($a_submissions as $i_pos => $a_fieldSubmissions) {
			$Component = $a_fieldSubmissions['component'];
			
			$a_fieldDecl = [
				'component' => $Component::COMPONENT_NAME,
			];
			
			$a_fieldConfig = $a_fieldSubmissions['config']->toStructured();
			if(isset($a_fieldConfig['name']) && $a_fieldConfig['name'] != $a_fieldConfig['old_name'] && !empty($a_fieldConfig['old_name'])) {
				$a_fieldRenames[$a_fieldConfig['old_name']] = $a_fieldConfig['name'];
			}
			unset($a_fieldConfig['old_name']);
			
			$a_fieldDecl = array_merge($a_fieldDecl, $a_fieldConfig);
			
			if(count($a_locales) == 1 && reset($a_locales) == '-') {
				$a_fieldDecl['locale'] = array_merge($a_fieldDecl['locales']['-']??[], $a_fieldSubmissions['locales']['-']->toStructured());
			}
			else {
				foreach($a_locales as $s_locale) {
					$a_fieldDecl['locales'][$s_locale] = array_merge($a_fieldDecl['locales'][$s_locale]??[], $a_fieldSubmissions['locales'][$s_locale]->toStructured());
				}
			}
			
			$a_fields[] = $a_fieldDecl;
		}
		
	//	$Form->mergeDefinition($DefinitionSubmission->toStructured());
	//	$Form->setFields($a_fields);
		/*
		$Form2 = clone $Form;
		$Form2->mergeDefinition($DefinitionSubmission->toStructured());
		$Form2->setFields($a_fields);
		
		$Updater = new Updater();
		$Form = $Updater->update($Form, $Form2, $a_fieldRenames);*/
		
		$a_newDefinition = array_merge($Form->getDefinition(), $DefinitionSubmission->toStructured());
		$a_newDefinition['fields'] = $a_fields;
		
		$Form->updateDefinition($a_newDefinition, $a_fieldRenames);
		
		return [
			'result' => true,
		];
	}
	
	private function generateDefinitionForm(Form $Form) {
		
		$a_definition = [
			'locale' => [],
			'submissions' => [
				'type' => 'none',
			],
			'layout' => [
				'bootstrap4' => [
					'col_left' => 'col-12',
					'col_right' => 'col-12',
				],
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
		
		$ConfigForm = $this->Reef->newTempForm();
		$ConfigForm->importDefinition($a_definition);
		
		return $ConfigForm;
	}
	
	private function generateConfigForm(Component $Component) {
		$a_configuration = $Component->getConfiguration();
		
		$a_configDefinition = [
			'locale' => $a_configuration['definition']['locale']??[],
			'submissions' => [
				'type' => 'none',
			],
			'layout' => [
				'bootstrap4' => [
					'col_left' => 'col-12',
					'col_right' => 'col-12',
				],
			],
			'fields' => $a_configuration['definition']['fields']??[],
		];
		
		if($a_configuration['category'] !== 'static') {
			array_unshift($a_configDefinition['fields'], [
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
			
			array_unshift($a_configDefinition['fields'], [
				'component' => 'reef:hidden',
				'name' => 'old_name',
			]);
		}
		
		$ComponentForm = $this->Reef->newTempForm();
		$ComponentForm->importDefinition($a_configDefinition);
		
		return $ComponentForm;
	}
	
	private function generateLocaleForm(Component $Component, string $s_locale) {
		if(empty($s_locale)) {
			$s_locale = '-';
		}
		$a_configuration = $Component->getConfiguration();
		
		$a_localeTitles = $a_configuration['locale'];
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
		
		$a_localeDefinition = [
			'locale' => [],
			'submissions' => [
				'type' => 'none',
			],
			'layout' => [
				'bootstrap4' => [
					'col_left' => 'col-12',
					'col_right' => 'col-12',
				],
			],
			'fields' => $a_fields,
		];
		
		$LocaleForm = $this->Reef->newTempForm();
		$LocaleForm->importDefinition($a_localeDefinition);
		
		return $LocaleForm;
	}
}
