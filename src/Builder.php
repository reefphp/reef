<?php

namespace Reef;

use Symfony\Component\Yaml\Yaml;
use Reef\Components\Component;
use Reef\Components\Field;

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
			
			$a_categories[$a_configuration['category']]['components'][] = [
				'configuration' => base64_encode(json_encode($a_configuration)),
				'html' => base64_encode($Component->getTemplate($Layout->getName())),
				'image' => $a_configuration['image'],
				'title' => $Component->trans('component_title'),
				'type' => $a_configuration['vendor'].':'.$a_configuration['name'],
				'declarationForms' => [
					['declType' => 'basic',    'form' => $this->generateDeclarationForm($Component, null, 'basic'   )],
					['declType' => 'advanced', 'form' => $this->generateDeclarationForm($Component, null, 'advanced')],
				],
				'localeForms' => [
					['declType' => 'basic',    'forms' => $this->generateLocaleForms($Component, null, 'basic'   )],
					['declType' => 'advanced', 'forms' => $this->generateLocaleForms($Component, null, 'advanced')],
				],
			];
			
		}
		$a_categories = array_values($a_categories);
		$a_categories[0]['open_default'] = true;
		$a_fields = [];
		foreach($Form->getFields() as $Field) {
			$s_declaration = base64_encode(json_encode($Field->getDeclaration()));
			
			$Component = $Field->getComponent();
			
			$a_fields[] = [
				'declaration' => $s_declaration,
				'type' => $Component::COMPONENT_NAME,
				'declarationForms' => [
					['declType' => 'basic',    'form' => $this->generateDeclarationForm($Component, $Field, 'basic'   )],
					['declType' => 'advanced', 'form' => $this->generateDeclarationForm($Component, $Field, 'advanced')],
				],
				'localeForms' => [
					['declType' => 'basic',    'forms' => $this->generateLocaleForms($Component, $Field, 'basic'   )],
					['declType' => 'advanced', 'forms' => $this->generateLocaleForms($Component, $Field, 'advanced')],
				],
			];
		}
		
		// Form definition
		$DefinitionForm = $this->generateDefinitionForm($Form);
		$DefinitionSubmission = $DefinitionForm->newSubmission();
		$DefinitionSubmission->fromStructured([
			'storage_name' => ($Form instanceof StoredForm) ? $Form->getStorageName() : 'temporary_form',
		]);
		
		$EmptyForm = clone $Form;
		$EmptyForm->setFields([]);
		$s_formHtml = $EmptyForm->generateFormHtml();
		
		$a_helpers = [];
		$a_helpers['CSSPRFX'] = $this->Reef->getOption('css_prefix');
		
		$Mustache = new \Mustache_Engine([
			'helpers' => $a_helpers,
			'cache' => $this->Reef->getOption('cache_dir').'mustache/',
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
			'builder_lang' => $this->Reef->transMultiple(['builder_basic', 'builder_advanced', 'builder_save']),
		]);
		
		return $s_html;
	}
	
	
	private function parseBuilderData(Form $Form, array $a_data) {
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
		
		foreach($a_data['fields']??[] as $i_pos => $a_field) {
			$Component = $Setup->getComponent($a_field['component']);
			
			// Validate declaration
			$a_declSubmissions = [];
			$a_localeSubmissions = [];
			foreach(['basic', 'advanced'] as $s_type) {
				if($s_type == 'basic') {
					$DeclForm = $Component->generateBasicDeclarationForm();
				}
				else {
					$DeclForm = $Component->generateAdvancedDeclarationForm();
				}
				$DeclSubmission = $DeclForm->newSubmission();
				
				$DeclSubmission->fromUserInput($a_field['declaration'][$s_type]??[]);
				
				$b_valid = $DeclSubmission->validate() && $b_valid;
				if(!$b_valid) {
					$a_errors[$i_pos]['declaration'][$s_type] = $DeclSubmission->getErrors();
				}
				$a_declSubmissions[$s_type] = $DeclSubmission;
				
				// Validate locale
				$a_localeSubmissions[$s_type] = [];
				foreach($a_locales as $s_locale) {
					if($s_type == 'basic') {
						$LocaleForm = $Component->generateBasicLocaleForm($s_locale);
					}
					else {
						$LocaleForm = $Component->generateAdvancedLocaleForm($s_locale);
					}
					$LocaleSubmission = $LocaleForm->newSubmission();
					$LocaleSubmission->fromUserInput($a_field['locale'][$s_type][$s_locale]??[]);
					
					$b_valid = $LocaleSubmission->validate() && $b_valid;
					if(!$b_valid) {
						$a_errors[$i_pos]['locale'][$s_locale] = $LocaleSubmission->getErrors();
					}
					$a_localeSubmissions[$s_type][$s_locale] = $LocaleSubmission;
				}
			}
			
			$a_submissions[$i_pos] = [
				'component' => $Component,
				'declaration' => $a_declSubmissions,
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
			
			$a_fieldConfig = array_merge(
				$a_fieldSubmissions['declaration']['basic']->toStructured(['skip_default' => true]),
				$a_fieldSubmissions['declaration']['advanced']->toStructured(['skip_default' => true])
			);
			if(isset($a_fieldConfig['name']) && $a_fieldSubmissions['declaration']['basic']->hasField('old_name')) {
				$s_oldName = $a_fieldSubmissions['declaration']['basic']->getFieldValue('old_name')->toStructured();
				if($s_oldName != $a_fieldConfig['name']) {
					$a_fieldRenames[$s_oldName] = $a_fieldConfig['name'];
				}
			}
			unset($a_fieldConfig['old_name']);
			
			$a_fieldDecl = array_merge($a_fieldDecl, $a_fieldConfig);
			
			if(count($a_locales) == 1 && reset($a_locales) == '-') {
				$a_fieldDecl['locale'] = array_merge(
					$a_fieldDecl['locales']['-']??[],
					$a_fieldSubmissions['locales']['basic']['-']->toStructured(['skip_default' => true]),
					$a_fieldSubmissions['locales']['advanced']['-']->toStructured(['skip_default' => true])
				);
			}
			else {
				foreach($a_locales as $s_locale) {
					$a_fieldDecl['locales'][$s_locale] = array_merge(
						$a_fieldDecl['locales'][$s_locale]??[],
						$a_fieldSubmissions['locales']['basic'][$s_locale]->toStructured(['skip_default' => true]),
						$a_fieldSubmissions['locales']['advanced'][$s_locale]->toStructured(['skip_default' => true])
					);
				}
			}
			
			$a_fields[] = $a_fieldDecl;
		}
		
		$a_newDefinition = array_merge($Form->getDefinition(), $DefinitionSubmission->toStructured(['skip_default' => true]));
		$a_newDefinition['fields'] = $a_fields;
		
		return [$a_newDefinition, $a_fieldRenames];
	}
	
	
	public function applyBuilderData(Form $Form, array $a_data) {
		[$a_newDefinition, $a_fieldRenames] = $this->parseBuilderData($Form, $a_data);
		
		$Form->updateDefinition($a_newDefinition, $a_fieldRenames);
		
		return [
			'result' => true,
		];
	}
	
	public function checkBuilderDataLoss(Form $Form, array $a_data) {
		[$a_newDefinition, $a_fieldRenames] = $this->parseBuilderData($Form, $a_data);
		
		return $Form->checkUpdateDataLoss($a_newDefinition, $a_fieldRenames);;
	}
	
	private function generateDefinitionForm(Form $Form) {
		
		$a_definition = [
			'locale' => [],
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
					'locales' => [
						'en_US' => [
							'title' => 'Form storage name',
						],
						'nl_NL' => [
							'title' => 'Formulier opslag naam',
						],
					],
					'default' => 'form_'.$Form->getReef()->getFormStorage()->next(),
				]
			],
		];
		
		$ConfigForm = $this->Reef->newTempForm();
		$ConfigForm->importValidatedDefinition($a_definition);
		
		return $ConfigForm;
	}
	
	private function generateDeclarationForm(Component $Component, ?Field $Field, string $s_type) {
		if($s_type == 'advanced') {
			$DeclarationForm = $Component->generateAdvancedDeclarationForm();
		}
		else {
			$DeclarationForm = $Component->generateBasicDeclarationForm();
		}
		
		if($Field === null) {
			$DeclarationForm->setIdPfx('__form_idpfx__'.$DeclarationForm->getIdPfx());
			$DeclarationSubmission = null;
		}
		else {
			$DeclarationSubmission = $DeclarationForm->newSubmission();
			$a_declaration = $Field->getDeclaration();
			if(isset($a_declaration['name'])) {
				$a_declaration['old_name'] = $a_declaration['name'];
			}
			$DeclarationSubmission->fromStructured($a_declaration);
		}
		
		return $DeclarationForm->generateFormHtml($DeclarationSubmission, ['main_var' => 'form_data['.$s_type.'_declaration]']);
	}
	
	private function generateLocaleForms(Component $Component, ?Field $Field, string $s_type) {
		$a_locales = $this->Reef->getOption('locales');
		
		$a_localeForms = [];
		foreach($a_locales as $s_locale) {
			if($s_type == 'advanced') {
				$LocaleForm = $Component->generateAdvancedLocaleForm($s_locale);
			}
			else {
				$LocaleForm = $Component->generateBasicLocaleForm($s_locale);
			}
			
			if($Field === null) {
				$LocaleForm->setIdPfx('__form_idpfx__'.$LocaleForm->getIdPfx());
				$LocaleSubmission = null;
			}
			else {
				$LocaleSubmission = $LocaleForm->newSubmission();
				$LocaleSubmission->fromStructured($Field->getDeclaration()['locales'][$s_locale] ?? $Field->getDeclaration()['locale'] ?? []);
			}
			
			$a_localeForms[] = [
				'locale' => $s_locale,
				'form' => $LocaleForm->generateFormHtml($LocaleSubmission, ['main_var' => 'form_data['.$s_type.'_locale]['.$s_locale.']']),
			];
		}
		
		return $a_localeForms;
	}
	
	
	
}
