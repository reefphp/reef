<?php

namespace Reef;

use Symfony\Component\Yaml\Yaml;
use \Reef\Form\Form;
use \Reef\Form\TempForm;
use \Reef\Form\AbstractStorableForm;
use \Reef\Form\StoredForm;
use \Reef\Form\TempStorableForm;
use \Reef\Components\Component;
use \Reef\Components\Field;
use \Reef\Exception\LogicException;
use \Reef\Exception\RuntimeException;
use \Reef\Exception\ValidationException;

/**
 * The builder provides functionality for manipulating forms using the
 * reef builder interface
 */
class Builder {
	
	/**
	 * All categories to display in the builder. Note that in particular, 'internal' is NOT one of these.
	 * @type string[]
	 */
	const CATEGORIES = [
		'static',
		'text',
		'choice',
		'other',
	];
	
	/**
	 * The Reef object
	 * @type Reef
	 */
	private $Reef;
	
	/**
	 * Settings for the builder
	 * @type array
	 */
	private $a_settings = [
		'submit_action' => null,
		'components' => null,
		'definition_form_creator' => null,
	];
	
	/**
	 * Constructor
	 * @param Reef $Reef The Reef object
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
		$this->a_settings['components'] = $Reef->getSetup()->getDefaultBuilderComponents();
	}
	
	/**
	 * Set a setting
	 * @param string $s_key The setting key
	 * @param string $s_val The setting value
	 */
	public function setSetting($s_key, $s_val) {
		if(array_key_exists($s_key, $this->a_settings)) {
			$this->a_settings[$s_key] = $s_val;
		}
	}
	
	/**
	 * Set multiple settings
	 * @param string[] $a_settings Array of settings
	 */
	public function setSettings($a_settings) {
		foreach($a_settings as $s_key => $s_val) {
			$this->setSetting($s_key, $s_val);
		}
	}
	
	/**
	 * Remove components from builder
	 * @param string|string[] $a_componentNames The component names
	 */
	public function removeComponents($a_componentNames) {
		if(!is_array($a_componentNames)) {
			$a_componentNames = [$a_componentNames];
		}
		
		$this->a_settings['components'] = array_diff($this->a_settings['components'], $a_componentNames);
	}
	
	/**
	 * Add components to builder
	 * @param string|string[] $a_componentNames The component names
	 */
	public function addComponents($a_componentNames) {
		if(!is_array($a_componentNames)) {
			$a_componentNames = [$a_componentNames];
		}
		
		$this->a_settings['components'] = array_unique(array_merge($this->a_settings['components'], $a_componentNames));
	}
	
	/**
	 * Create HTML for the builder
	 * @param Form $Form The form to generate the builder for
	 * @param array $a_options Array of options, to choose from:
	 *  - definition_submission: array of definition submission values
	 * @return string The builder HTML
	 */
	public function generateBuilderHtml(Form $Form, $a_options = []) {
		$Layout = $this->Reef->getSetup()->getLayout();
		$a_componentMapping = $this->Reef->getSetup()->getComponentMapping();
		$a_categories = [];
		foreach(self::CATEGORIES as $s_category) {
			$a_categories[$s_category] = [
				'category' => $s_category,
				'category_title' => $this->Reef->trans('cat_'.$s_category),
				'components' => [],
			];
		}
		
		$a_components = $this->a_settings['components'] ?? $this->Reef->getSetup()->getDefaultBuilderComponents();
		
		$a_inexistentComponents = array_diff($a_components, array_keys($a_componentMapping));
		if(!empty($a_inexistentComponents)) {
			throw new RuntimeException("Inexistent components ".implode(', ', $a_inexistentComponents));
		}
		
		$a_locales = $this->Reef->getOption('locales');
		
		foreach($a_components as $s_name) {
			$Component = $a_componentMapping[$s_name];
			$a_configuration = $Component->getConfiguration();
			
			if(!isset($a_categories[$a_configuration['category']])) {
				throw new LogicException('Components in the category "'.$a_configuration['category'].'" cannot be used in the builder');
			}
			
			$a_categories[$a_configuration['category']]['components'][] = [
				'configuration' => base64_encode(json_encode($a_configuration)),
				'operators' => base64_encode(json_encode($Component->getBuilderOperators())),
				'html' => base64_encode($Component->getTemplate($Layout->getName())),
				'component_image_hash' => 'asset:component:'.$Component::COMPONENT_NAME.':component_image',
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
		foreach($a_categories as $s_category => $a_category) {
			if(empty($a_category['components'])) {
				unset($a_categories[$s_category]);
			}
		}
		$a_categories = array_values($a_categories);
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
		$a_definitionSubmission = $a_options['definition_submission'] ?? [];
		$a_definitionSubmission['storage_name'] = ($Form instanceof AbstractStorableForm) ? $Form->getStorageName() : 'temporary_form';
		$DefinitionSubmission->fromStructured($a_definitionSubmission);
		
		$EmptyForm = clone $Form;
		$EmptyForm->setFields([]);
		$s_formHtml = $EmptyForm->generateFormHtml();
		
		$Mustache = $this->Reef->newMustache();
		
		$Mustache->setLoader(new \Reef\Mustache\FilesystemLoader($this->Reef, Reef::getDir() . 'src/'));
		$Template = $Mustache->loadTemplate('view/builder.mustache');
		
		$s_html = $Template->render([
			'categories' => $a_categories,
			'formConfig' => base64_encode(json_encode(array_merge(
				array_subset($Form->getPartialDefinition(), ['locale']),
				[
					'layout_name' => $Layout->getName(),
					'layout' => $Layout->view(),
				]
			))),
			'formHtml' => $s_formHtml,
			'settings' => \Reef\array_subset($this->a_settings, ['submit_action']),
			'fields' => $a_fields,
			'definitionForm' => $DefinitionForm->generateFormHtml($DefinitionSubmission, ['main_var' => 'definition']),
			'multipleLocales' => (count($a_locales) > 1),
			'builder_lang' => $this->Reef->transMultiple([
				'rf_yes',
				'rf_no',
				'builder_basic',
				'builder_advanced',
				'builder_save',
				'builder_save_stage_validate',
				'builder_save_stage_data',
				'builder_save_stage_save',
				'builder_save_dataloss_potential_title',
				'builder_save_dataloss_definite_title',
				'builder_save_dataloss_confirm',
				'builder_delete_field_confirm',
			]),
		]);
		
		return $s_html;
	}
	
	/**
	 * Parse builder data into a new form definition and a list of field renames
	 * @param Form $Form The form
	 * @param array $a_data The submitted builder data
	 * @return array [(array) new form definition, (string[]) field renames, (Submission) The definition submission]
	 */
	private function parseBuilderData(Form $Form, array $a_data) {
		$Setup = $this->Reef->getSetup();
		$a_locales = $this->Reef->getOption('locales');
		
		$a_errors = [];
		
		// Validate form definition
		$DefinitionForm = $this->generateDefinitionForm($Form);
		$DefinitionSubmission = $DefinitionForm->newSubmission();
		
		$DefinitionSubmission->fromUserInput($a_data['definition']??[]);
		
		if(!$DefinitionSubmission->validate()) {
			$a_errors[-1] = $DefinitionSubmission->getErrors();
		}
		
		// Validate all fields
		$a_submissions = [];
		
		$a_fields = json_decode($a_data['fields']??'[]', true);
		if(!is_array($a_fields)) {
			throw new ValidationException([
				-1 => ['Corrupt input data!'],
			]);
		}
		
		foreach($a_fields as $i_index => $a_field) {
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
				
				if(!$DeclSubmission->validate()) {
					$a_errors[$i_index]['declaration'][$s_type] = $DeclSubmission->getErrors();
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
					
					if(!$LocaleSubmission->validate()) {
						$a_errors[$i_index]['locale'][$s_type][$s_locale] = $LocaleSubmission->getErrors();
					}
					$a_localeSubmissions[$s_type][$s_locale] = $LocaleSubmission;
				}
			}
			
			$a_submissions[$i_index] = [
				'component' => $Component,
				'declaration' => $a_declSubmissions,
				'locales' => $a_localeSubmissions,
			];
		}
		
		if(!empty($a_errors)) {
			throw new ValidationException($a_errors);
		}
		
		// Turn the field form submissions into field declarations
		$a_fields = $a_fieldRenames = [];
		foreach($a_submissions as $i_index => $a_fieldSubmissions) {
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
			
			if(count($a_locales) == 1 && reset($a_locales) == '_no_locale') {
				$a_fieldDecl['locale'] = array_merge(
					$a_fieldDecl['locales']['_no_locale']??[],
					$a_fieldSubmissions['locales']['basic']['_no_locale']->toStructured(['skip_default' => true]),
					$a_fieldSubmissions['locales']['advanced']['_no_locale']->toStructured(['skip_default' => true])
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
		
		// Create the new form definition
		$a_newDefinition = array_merge($Form->getPartialDefinition(), array_subset($DefinitionSubmission->toStructured(['skip_default' => true]), ['storage_name']));
		$a_newDefinition['fields'] = $a_fields;
		
		return [$a_newDefinition, $a_fieldRenames, $DefinitionSubmission];
	}
	
	/**
	 * Process builder data and return the result
	 * @param Form &$Form The form to modify. In case a TempStorableForm is passed, upon applying the changes this variable
	 *                    will be replaced with a corresponding StoredForm object
	 * @param array $a_data The builder data from the builder
	 * @param ?callable $fn_callback Callback to apply just before exiting. The return array
	 *                  (@see processBuilderData_return()) is passed as first argument, the definition submission as second
	 * @return array The return data to be returned (in JSON format) to the builder
	 */
	public function processBuilderData_return(Form &$Form, array $a_data, ?callable $fn_callback = null) : array {
		try {
			[$a_newDefinition, $a_fieldRenames, $DefinitionSubmission] = $this->parseBuilderData($Form, $a_data);
			
			$a_return = null;
			
			if(!isset($a_data['allow_dataloss']) || $a_data['allow_dataloss'] !== 'yes') {
				// Check dataloss
				$a_dataloss = $Form->checkUpdateDataLoss($a_newDefinition, $a_fieldRenames);
				
				if(!empty(array_diff($a_dataloss, [Updater::DATALOSS_NO]))) {
					$a_return = [
						'dataloss' => $a_dataloss,
					];
				}
			}
			
			if($a_return === null) {
				// Apply
				$Form->updateDefinition($a_newDefinition, $a_fieldRenames);
				
				if($Form instanceof TempStorableForm) {
					$Form = $Form->toStoredForm();
				}
				
				$a_return = [
					'result' => true,
				];
			}
		}
		catch(\Reef\Exception\ValidationException $e) {
			$a_return = [
				'errors' => $e->getErrors(),
			];
			$DefinitionSubmission = null;
		}
		
		$a_return['result'] = !empty($a_return['result']);
		
		if($fn_callback !== null) {
			$fn_callback($a_return, $DefinitionSubmission);
		}
		
		return $a_return;
	}
	
	/**
	 * Process builder data and write the result to output, exiting afterwards.
	 * This function does NOT return!
	 * @param Form &$Form The form to modify. In case a TempStorableForm is passed, upon applying the changes this variable
	 *                    will be replaced with a corresponding StoredForm object
	 * @param array $a_data The builder data from the builder
	 * @param ?callable $fn_callback Callback to apply just before exiting. The return array
	 *                  (@see processBuilderData_return()) is passed as first argument, the definition submission as second
	 */
	public function processBuilderData_write(Form &$Form, array $a_data, ?callable $fn_callback = null) {
		$a_return = $this->processBuilderData_return($Form, $a_data, $fn_callback);
		
		echo json_encode($a_return);
		die();
	}
	
	/**
	 * Generate the definition form, allowing to edit form properties except
	 * for the field declarations
	 * @param Form $Form The form to generate the definition form for
	 * @return TempForm
	 */
	private function generateDefinitionForm(Form $Form) {
		
		$a_definition = [
			'locale' => [],
			'layout' => [
				'bootstrap4' => [
					'break' => [],
				],
			],
			'fields' => [
				[
					'component' => 'reef:text_line',
					'name' => 'storage_name',
					'required' => true,
					'regexp' => \Reef\Reef::NAME_REGEXP,
					'locales' => $this->Reef->transMultipleLocales(['title' => 'builder_form_storage_name'], $this->Reef->getOption('locales')),
					'default' => 'form_'.$Form->getReef()->getFormStorage()->next(),
				]
			],
		];
		
		$ConfigForm = $this->Reef->newValidTempForm($a_definition);
		
		if(isset($this->a_settings['definition_form_creator']) && is_callable($this->a_settings['definition_form_creator'])) {
			$Creator = $ConfigForm->newCreator();
			$this->a_settings['definition_form_creator']($Creator);
			$Creator->apply();
		}
		
		return $ConfigForm;
	}
	
	/**
	 * Generate a declaration form for a component
	 * @param Component $Component The component to generate the form for
	 * @param ?Field $Field The field to generate the form for, or null for a 'new' field
	 * @param string $s_type Either 'basic' or 'advanced'
	 * @return string The declaration form html
	 */
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
	
	/**
	 * Generate a locale form for a component
	 * @param Component $Component The component to generate the form for
	 * @param ?Field $Field The field to generate the form for, or null for a 'new' field
	 * @param string $s_type Either 'basic' or 'advanced'
	 * @return array The locale form html for each locale
	 */
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
