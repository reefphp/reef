<?php

namespace Reef;

use \Reef\Exception\IOException;
use Symfony\Component\Yaml\Yaml;

class Form {
	
	private $Reef;
	private $SubmissionStorage;
	private $FormAssets;
	
	private $i_formId;
	private $a_locale;
	private $a_formConfig = [];
	private $a_fields = [];
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	public function getFormId() {
		return $this->i_formId;
	}
	
	public function getFormConfig() {
		return $this->a_formConfig;
	}
	
	public function getFields() {
		return $this->a_fields;
	}
	
	public function getReef() {
		return $this->Reef;
	}
	
	public function getSubmissionStorage() {
		return $this->SubmissionStorage;
	}
	
	public function getFormAssets() {
		if($this->FormAssets == null) {
			$this->FormAssets = new FormAssets($this);
		}
		
		return $this->FormAssets;
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
		$Mapper = $this->Reef->getComponentMapper();
		
		$this->a_formConfig = $a_declaration;
		unset($this->a_formConfig['fields']);
		unset($this->a_formConfig['submissions']);
		
		$this->SubmissionStorage = $this->Reef->getStorage($a_declaration['submissions']);
		
		$this->a_formConfig['layout']['name'] = $this->a_formConfig['layout']['name'] ?? 'bootstrap4';
		
		foreach($a_declaration['fields'] as $s_id => $a_config) {
			$this->a_fields[$s_id] = $Mapper->getField($a_config, $this);
		}
	}
	
	public function generateDeclaration() : string {
		
	}
	
	public function save() {
		$a_declaration = $this->generateDeclaration();
		
		if($this->i_formId == null) {
			$this->i_formId = $this->Reef->getFormStorage()->insert($a_declaration);
		}
		else {
			$this->Reef->getFormStorage()->update($this->i_formId, $a_declaration);
		}
	}
	
	public function load(int $i_formId) {
		$this->importDeclaration($this->Reef->getFormStorage()->get($i_formId));
		$this->i_formId = $i_formId;
	}
	
	public function delete() {
		if($this->i_formId == null) {
			throw new \Exception("Unsaved form.");
		}
		$this->Reef->getFormStorage()->delete($this->i_formId);
	}
	
	public function getSubmissionIds() {
		return $this->SubmissionStorage->list();
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
		$a_helpers['locale'] = $this->getLocale($a_options['locale']??null);
		unset($a_helpers['locales']);
		
		$a_helpers['CSSPRFX'] = $this->Reef->getOption('css_prefix');
		
		$Mustache = new \Mustache_Engine([
			'helpers' => $a_helpers,
		]);
		
		foreach($this->a_fields as $Field) {
			$s_templateDir = null;
			$s_viewfile = 'view/'.$this->a_formConfig['layout']['name'].'/form.mustache';
			
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
			$a_vars = $Field->view_form($Submission->getFieldValue($Field->getConfig()['name']), array_subset($a_options, ['locale']));
			
			$s_html = $Template->render([
				'field' => $a_vars,
			]);
			
			$a_fields[] = [
				'html' => $s_html,
			];
		}
		
		$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader(__DIR__));
		$Template = $Mustache->loadTemplate('view/'.$this->a_formConfig['layout']['name'].'/form.mustache');
		$s_html = $Template->render([
			'fields' => $a_fields,
			'config_base64' => base64_encode(json_encode(\Reef\array_subset($this->a_formConfig, ['main_var', 'layout']))),
		]);
		
		return $s_html;
	}
	
	private function getLocale($a_locales = null) {
		$a_locale = null;
		
		if(!is_array($a_locales)) {
			$a_locales = [$a_locales];
		}
		
		$a_locales[] = $this->a_formConfig['default_locale']??null;
		$a_locales[] = 'en_US';
		$a_locales = array_unique(array_filter($a_locales));
		
		// Find user-defined locale
		if(isset($this->a_formConfig['locales'])) {
			foreach($a_locales as $s_loc) {
				if(isset($this->a_formConfig['locales'][$s_loc])) {
					$a_locale = $this->a_formConfig['locales'][$s_loc];
					break;
				}
			}
		}
		
		// Find user-defined general locale
		if($a_locale === null && isset($this->a_formConfig['locale'])) {
			$a_locale = $this->a_formConfig['locale'];
		}
		
		if($a_locale === null) {
			throw new \InvalidArgumentException("Could not find locale for form.");
		}
		
		// Find Reef-defined locale
		$a_locale = array_merge(
			$this->getReef()->getLocale($a_locales),
			$a_locale
		);
		
		return $a_locale;
	}
	
	public function trans($s_key, $a_locales = null) {
		return $this->getLocale($a_locales)[$s_key]??null;
	}
	
	public function newSubmission() {
		return new Submission($this);
	}
	
}
