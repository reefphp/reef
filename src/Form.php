<?php

namespace Reef;

use \Reef\Trait_Locale;
use \Reef\Exception\IOException;
use Symfony\Component\Yaml\Yaml;

class Form {
	
	use Trait_Locale;
	
	private $Reef;
	private $SubmissionStorage;
	private $FormAssets;
	
	private $i_formId;
	private $s_idPfx;
	private $a_locale;
	private $a_formConfig = [];
	private $a_fields = [];
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
		$this->s_idPfx = unique_id();
	}
	
	public function getFormId() {
		return $this->i_formId;
	}
	
	public function getFormConfig() {
		return $this->a_formConfig;
	}
	
	public function getStorageName() {
		return $this->a_formConfig['storage_name']??null;
	}
	
	public function setStorageName($s_newStorageName) {
		$this->Reef->getDataStore()->changeSubmissionStorageName($this, $s_newStorageName);
		$this->a_formConfig['storage_name'] = $s_newStorageName;
	}
	
	public function getFields() {
		return $this->a_fields;
	}
	
	public function getValueFields() {
		$a_fields = $this->a_fields;
		foreach($a_fields as $i => $Field) {
			if($Field->getComponent()->getDefinition()['category'] == 'static') {
				unset($a_fields[$i]);
			}
		}
		return $a_fields;
	}
	
	public function getValueFieldsByName() {
		$a_fields = [];
		foreach($this->getValueFields() as $Field) {
			$a_fields[$Field->getConfig()['name']] = $Field;
		}
		return $a_fields;
	}
	
	public function getReef() {
		return $this->Reef;
	}
	
	public function getSubmissionStorage() {
		if(empty($this->a_formConfig['storage_name']??null)) {
			return null;
		}
		
		if(empty($this->SubmissionStorage)) {
			$this->SubmissionStorage = $this->Reef->getSubmissionStorage($this);
		}
		
		return $this->SubmissionStorage;
	}
	
	public function getFormAssets() {
		if($this->FormAssets == null) {
			$this->FormAssets = new FormAssets($this);
		}
		
		return $this->FormAssets;
	}
	
	public function getIdPfx() {
		return $this->s_idPfx;
	}
	
	public function setIdPfx($s_idPfx) {
		$this->s_idPfx = $s_idPfx;
	}
	
	public function importDeclarationFile(string $s_filename) {
		if(!file_exists($s_filename) || !is_readable($s_filename)) {
			throw new IOException('Could not find file "'.$s_filename.'".');
		}
		
		$a_declaration = Yaml::parseFile($s_filename);
		
		$this->importDeclaration($a_declaration);
	}
	
	public function importDeclarationString(string $s_declaration) {
		$a_declaration = Yaml::parse($s_declaration);
		
		$this->importDeclaration($a_declaration);
	}
	
	public function importDeclaration(array $a_declaration) {
		$this->a_formConfig = $a_declaration;
		unset($this->a_formConfig['fields']);
		
		$this->setFields($a_declaration['fields']??[]);
	}
	
	public function newDeclaration(array $a_declaration) {
		if(empty($a_declaration['storage_name'])) {
			throw new \Exception("Missing storage_name");
		}
		
		$this->a_formConfig['storage_name'] = $a_declaration['storage_name'];
		$this->updateDeclaration($a_declaration);
	}
	
	public function updateDeclaration(array $a_declaration, array $a_fieldRenames = []) {
		$Form2 = clone $this;
		$Form2->importDeclaration($a_declaration);
		
		$Updater = new Updater();
		$Updater->update($this, $Form2, $a_fieldRenames);
	}
	
	public function mergeConfig(array $a_partialDeclaration) {
		$this->a_formConfig = array_merge($this->a_formConfig, $a_partialDeclaration);
	}
	
	public function setFields(array $a_fields) {
		$Setup = $this->Reef->getSetup();
		
		$this->a_fields = [];
		foreach($a_fields as $s_id => $a_config) {
			$this->a_fields[$s_id] = $Setup->getField($a_config, $this);
		}
	}
	
	public function generateDeclaration() : array {
		$a_declaration = $this->a_formConfig;
		
		$a_declaration['fields'] = [];
		
		foreach($this->a_fields as $s_id => $Field) {
			$a_declaration['fields'][$s_id] = $Field->getConfig();
		}
		
		return $a_declaration;
	}
	
	public function save() {
		$a_declaration = $this->generateDeclaration();
		
		if($this->i_formId == null) {
			$this->i_formId = $this->Reef->getFormStorage()->insert(['declaration' => json_encode($a_declaration)]);
		}
		else {
			$this->Reef->getFormStorage()->update($this->i_formId, ['declaration' => json_encode($a_declaration)]);
		}
	}
	
	public function saveAs(int $i_formId) {
		if($this->i_formId !== null) {
			throw new \Exception("Already saved form");
		}
		
		$a_declaration = $this->generateDeclaration();
		$this->i_formId = $this->Reef->getFormStorage()->insertAs($i_formId, ['declaration' => json_encode($a_declaration)]);
	}
	
	public function load(int $i_formId) {
		$this->importDeclaration(json_decode($this->Reef->getFormStorage()->get($i_formId)['declaration'], true));
		$this->i_formId = $i_formId;
	}
	
	public function delete() {
		$this->Reef->getDataStore()->deleteSubmissionStorageIfExists($this);
		
		if($this->i_formId !== null) {
			$this->Reef->getFormStorage()->delete($this->i_formId);
		}
	}
	
	public function getSubmissionIds() {
		return $this->getSubmissionStorage()->list();
	}
	
	public function getSubmission(int $i_submissionId) : Submission {
		$Submission = $this->newSubmission();
		
		$Submission->load($i_submissionId);
		
		return $Submission;
	}
	
	public function generateFormHtml(Submission $Submission = null, $a_options = []) {
		$a_fields = [];
		
		if($Submission == null) {
			$Submission = $this->newSubmission();
			$Submission->emptySubmission();
		}
		
		$a_helpers = $this->a_formConfig;
		unset($a_helpers['storage_name']);
		$a_helpers['locale'] = $this->getLocale($a_options['locale']??null);
		unset($a_helpers['locales']);
		
		$a_helpers['CSSPRFX'] = $this->Reef->getOption('css_prefix');
		$a_helpers['form_idpfx'] = $this->s_idPfx;
		$a_helpers['main_var'] = $a_options['main_var'] ?? 'reef_data';
		
		$Layout = $this->Reef->getSetup()->getLayout();
		$a_helpers['layout_name'] = $Layout->getName();
		$a_helpers['layout'] = $Layout->getMergedConfig($a_helpers['layout'][$Layout->getName()] ?? []);
		
		$Mustache = new \Mustache_Engine([
			'helpers' => $a_helpers,
		]);
		
		foreach($this->a_fields as $Field) {
			$s_templateDir = null;
			$s_viewfile = 'view/'.$Layout->getName().'/form.mustache';
			
			$a_classes = $Field->getComponent()->getInheritanceList();
			foreach($a_classes as $s_class) {
				if(file_exists($s_class::getDir() . $s_viewfile)) {
					$s_templateDir = $s_class::getDir();
					break;
				}
			}
			
			if($s_templateDir === null) {
				throw new \Exception("Could not find form template file for field '".$Field->getConfig()['name']."'.");
			}
			
			$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader($s_templateDir));
			$Template = $Mustache->loadTemplate($s_viewfile);
			$Value = ($Field->getComponent()->getDefinition()['category'] == 'static') ? null : $Submission->getFieldValue($Field->getConfig()['name']);
			$a_vars = $Field->view_form($Value, array_subset($a_options, ['locale']));
			
			$s_html = $Template->render([
				'field' => $a_vars,
			]);
			
			$a_fields[] = [
				'html' => $s_html,
			];
		}
		
		$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader(__DIR__));
		$Template = $Mustache->loadTemplate('view/'.$Layout->getName().'/form.mustache');
		$s_html = $Template->render([
			'fields' => $a_fields,
			'config_base64' => base64_encode(json_encode(\Reef\array_subset($a_helpers, ['main_var', 'layout_name', 'layout']))),
		]);
		
		return $s_html;
	}
	
	protected function fetchBaseLocale($s_locale) {
		if(!empty($s_locale) && isset($this->a_formConfig['locales'][$s_locale])) {
			return $this->a_formConfig['locales'][$s_locale];
		}
		else if(isset($this->a_formConfig['locale'])) {
			return $this->a_formConfig['locale'];
		}
		else {
			return [];
		}
	}
	
	public function getCombinedLocaleSources($s_locale) {
		return $this->combineLocaleSources(
			$this->getOwnLocaleSource($s_locale),
			$this->Reef->getOwnLocaleSource($s_locale)
		);
	}
	
	protected function getDefaultLocale() {
		return $this->a_formConfig['default_locale']??null;
	}
	
	public function newSubmission() {
		return new Submission($this);
	}
	
}
