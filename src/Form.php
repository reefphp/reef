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
	private $a_components = [];
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	public function getFormId() {
		return $this->i_formId;
	}
	
	public function getComponents() {
		return $this->a_components;
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
		unset($this->a_formConfig['components']);
		unset($this->a_formConfig['submissions']);
		
		$this->SubmissionStorage = $this->Reef->getStorage($a_declaration['submissions']);
		
		$this->a_formConfig['layout']['name'] = $this->a_formConfig['layout']['name'] ?? 'bootstrap4';
		
		foreach($a_declaration['components'] as $s_id => $a_config) {
			$this->a_components[$s_id] = $Mapper->get($a_config);
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
		$a_components = [];
		
		if($Submission == null) {
			$Submission = $this->newSubmission();
			$Submission->emptySubmission();
		}
		
		$a_formConfig = $this->a_formConfig;
		$a_formConfig['locale'] = $this->getLocale($a_formConfig, $a_options['locale']??null);
		unset($a_formConfig['locales']);
		
		$Mustache = new \Mustache_Engine([
			'helpers' => $a_formConfig,
		]);
		
		foreach($this->a_components as $Component) {
			
			$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader($Component::getDir()));
			$Template = $Mustache->loadTemplate('view/'.$this->a_formConfig['layout']['name'].'/form.mustache');
			$a_vars = $Component->view_form($Submission->getComponentValue($Component->getConfig()['name'])->toTemplateVar());
			
			$a_vars['locale'] = $this->getLocale($a_vars, $a_options['locale']??null);
			unset($a_vars['locales']);
			
			$s_html = $Template->render([
				'component' => $a_vars,
			]);
				
			$a_components[] = [
				'html' => $s_html,
			];
		}
		
		$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader(__DIR__));
		$Template = $Mustache->loadTemplate('view/'.$this->a_formConfig['layout']['name'].'/form.mustache');
		$s_html = $Template->render([
			'components' => $a_components,
		]);
		
		return $s_html;
	}
	
	private function getLocale($a_vars, ?string $s_locale) {
		if(isset($a_vars['locales'])) {
			$a_locales = array_unique(array_filter([
				$s_locale,
				$this->a_formConfig['default_locale']??null,
				'en',
			]));
			
			foreach($a_locales as $s_loc) {
				if(isset($a_vars['locales'][$s_loc])) {
					return $a_vars['locales'][$s_loc];
				}
			}
		}
		
		if(isset($a_vars['locale'])) {
			return $a_vars['locale'];
		}
		
		throw new \InvalidArgumentException("Could not find locale for component '".$a_vars['name']."'.");
	}
	
	public function newSubmission() {
		return new Submission($this);
	}
	
}
