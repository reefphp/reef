<?php

namespace Reef;

use \Reef\Trait_Locale;
use \Reef\Exception\IOException;
use Symfony\Component\Yaml\Yaml;

abstract class Form {
	
	use Trait_Locale;
	
	protected $Reef;
	protected $FormAssets;
	
	protected $s_idPfx;
	protected $a_locale;
	protected $a_formConfig = [];
	protected $a_fields = [];
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
		$this->s_idPfx = unique_id();
	}
	
	public function getFormConfig() {
		return $this->a_formConfig;
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
			$a_fields[$Field->getConfig()['name']] = $Field;
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
			$Value = ($Field->getComponent()->getConfiguration()['category'] == 'static') ? null : $Submission->getFieldValue($Field->getConfig()['name']);
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
	
	abstract public function updateDeclaration(array $a_declaration, array $a_fieldRenames = []);
	
	abstract public function newSubmission();
	
}
