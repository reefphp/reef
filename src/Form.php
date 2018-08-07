<?php

namespace Reef;

use \Reef\Trait_Locale;
use \Reef\Exception\ResourceNotFoundException;
use Symfony\Component\Yaml\Yaml;

abstract class Form {
	
	use Trait_Locale;
	
	protected $Reef;
	protected $FormAssets;
	
	protected $s_idPfx;
	protected $a_locale;
	protected $a_definition = [];
	protected $a_fields = [];
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
		$this->s_idPfx = unique_id();
	}
	
	public function getDefinition() {
		return $this->a_definition;
	}
	
	public function getFields() {
		return $this->a_fields;
	}
	
	public function getValueFields() {
		$a_fields = $this->a_fields;
		foreach($a_fields as $i => $Field) {
			if($Field->getComponent()->getConfiguration()['category'] == 'static') {
				unset($a_fields[$i]);
			}
		}
		return $a_fields;
	}
	
	public function getValueFieldsByName() {
		$a_fields = [];
		foreach($this->getValueFields() as $Field) {
			$a_fields[$Field->getDeclaration()['name']] = $Field;
		}
		return $a_fields;
	}
	
	public function getReef() {
		return $this->Reef;
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
	
	public function newCreator() {
		return new \Reef\Creator\Creator($this);
	}
	
	public function importDefinitionFile(string $s_filename) {
		if(!file_exists($s_filename) || !is_readable($s_filename)) {
			throw new ResourceNotFoundException('Could not find file "'.$s_filename.'".');
		}
		
		$a_definition = Yaml::parseFile($s_filename);
		
		$this->importDefinition($a_definition);
	}
	
	public function importDefinitionString(string $s_definition) {
		$a_definition = Yaml::parse($s_definition);
		
		$this->importDefinition($a_definition);
	}
	
	public function importDefinition(array $a_definition) {
		$this->Reef->checkDefinition($a_definition);
		$this->importValidatedDefinition($a_definition);
	}
	
	public function importValidatedDefinition(array $a_definition) {
		$this->a_definition = $a_definition;
		unset($this->a_definition['fields']);
		
		$this->setFields($a_definition['fields']??[]);
	}
	
	public function mergeDefinition(array $a_partialDefinition) {
		$this->a_definition = array_merge($this->a_definition, $a_partialDefinition);
	}
	
	public function setFields(array $a_fields) {
		$Setup = $this->Reef->getSetup();
		
		$this->a_fields = [];
		foreach($a_fields as $s_id => $a_declaration) {
			$this->a_fields[$s_id] = $Setup->getField($a_declaration, $this);
		}
	}
	
	public function generateDefinition() : array {
		$a_definition = $this->a_definition;
		
		$a_definition['fields'] = [];
		
		foreach($this->a_fields as $s_id => $Field) {
			$a_definition['fields'][$s_id] = $Field->getDeclaration();
		}
		
		return $a_definition;
	}
	
	public function generateFormHtml(Submission $Submission = null, $a_options = []) {
		$a_fields = [];
		
		if($Submission == null) {
			$Submission = $this->newSubmission();
			$Submission->emptySubmission();
		}
		
		$a_data = [];
		$a_data['main_var'] = $a_options['main_var'] ?? 'reef_data';
		$Layout = $this->Reef->getSetup()->getLayout();
		$a_data['layout_name'] = $Layout->getName();
		$a_data['layout'] = $Layout->getMergedConfig($this->a_definition['layout'][$Layout->getName()] ?? []);
		$a_data['assets_url'] = $this->Reef->getOption('assets_url');
		
		$Mustache = $this->Reef->newMustache();
		$Mustache->addHelper('form_idpfx', $this->s_idPfx);
		$Mustache->addHelper('main_var', $a_data['main_var']);
		$Mustache->addHelper('layout', $a_data['layout']);
		
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
				// @codeCoverageIgnoreStart
				throw new ResourceNotFoundException("Could not find form template file for field '".$Field->getDeclaration()['name']."'.");
				// @codeCoverageIgnoreEnd
			}
			
			$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader($s_templateDir));
			$Template = $Mustache->loadTemplate($s_viewfile);
			$Value = ($Field->getComponent()->getConfiguration()['category'] == 'static') ? null : $Submission->getFieldValue($Field->getDeclaration()['name']);
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
			'config_base64' => base64_encode(json_encode($a_data)),
		]);
		
		return $s_html;
	}
	
	public function generateSubmissionHtml(Submission $Submission, $a_options = []) {
		$a_fields = [];
		
		$a_data = [];
		$Layout = $this->Reef->getSetup()->getLayout();
		$a_data['layout_name'] = $Layout->getName();
		$a_data['layout'] = $Layout->getMergedConfig($this->a_definition['layout'][$Layout->getName()] ?? []);
		$a_data['assets_url'] = $this->Reef->getOption('assets_url');
		
		$Mustache = $this->Reef->newMustache();
		$Mustache->addHelper('form_idpfx', $this->s_idPfx);
		$Mustache->addHelper('layout', $a_data['layout']);
		
		foreach($this->a_fields as $Field) {
			$s_templateDir = null;
			$s_viewfile = 'view/'.$Layout->getName().'/submission.mustache';
			
			$a_classes = $Field->getComponent()->getInheritanceList();
			foreach($a_classes as $s_class) {
				if(file_exists($s_class::getDir() . $s_viewfile)) {
					$s_templateDir = $s_class::getDir();
					break;
				}
			}
			
			if($s_templateDir === null) {
				// @codeCoverageIgnoreStart
				throw new ResourceNotFoundException("Could not find submission template file for field '".$Field->getDeclaration()['name']."'.");
				// @codeCoverageIgnoreEnd
			}
			
			$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader($s_templateDir));
			$Template = $Mustache->loadTemplate($s_viewfile);
			$Value = ($Field->getComponent()->getConfiguration()['category'] == 'static') ? null : $Submission->getFieldValue($Field->getDeclaration()['name']);
			$a_vars = $Field->view_submission($Value, array_subset($a_options, ['locale']));
			
			$s_html = $Template->render([
				'field' => $a_vars,
			]);
			
			$a_fields[] = [
				'html' => $s_html,
			];
		}
		
		$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader(__DIR__));
		$Template = $Mustache->loadTemplate('view/'.$Layout->getName().'/submission.mustache');
		$s_html = $Template->render([
			'fields' => $a_fields,
			'config_base64' => base64_encode(json_encode($a_data)),
		]);
		
		return $s_html;
	}
	
	protected function fetchBaseLocale($s_locale) {
		if(!empty($s_locale) && isset($this->a_definition['locales'][$s_locale])) {
			return $this->a_definition['locales'][$s_locale];
		}
		else if(isset($this->a_definition['locale'])) {
			return $this->a_definition['locale'];
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
		return $this->a_definition['default_locale']??$this->Reef->getOption('default_locale');
	}
	
	abstract public function updateDefinition(array $a_definition, array $a_fieldRenames = []);
	
	abstract public function checkUpdateDataLoss(array $a_definition, array $a_fieldRenames = []);
	
	abstract public function newSubmission();
	
}
