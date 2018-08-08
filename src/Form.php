<?php

namespace Reef;

use \Reef\Trait_Locale;
use \Reef\Exception\ResourceNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * General Form class
 * 
 * Holds general form functionality. A form is defined by a set of fields along with additional
 * configuration, all defined in the form definition.
 */
abstract class Form {
	
	use Trait_Locale;
	
	/**
	 * The Reef object this Form belongs to
	 * 
	 * @var Reef
	 */
	protected $Reef;
	
	/**
	 * The FormAssets object used by this form
	 * 
	 * @var FormAssets
	 */
	protected $FormAssets;
	
	/**
	 * The id prefix used for this form. The id prefix is appended to the id="" HTML field
	 * 
	 * @var string
	 */
	protected $s_idPfx;
	
	/**
	 * The definition array of this form. This is the parsed YAML form definition, except the
	 * fields are absent and moved to $a_fields
	 * 
	 * @var array
	 */
	protected $a_definition = [];
	
	/**
	 * Array of fields in this form
	 * 
	 * @var \Reef\Components\Field[]
	 */
	protected $a_fields = [];
	
	/**
	 * Constructor
	 * 
	 * @param Reef $Reef The Reef object this Form belongs to
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
		$this->s_idPfx = unique_id();
	}
	
	/**
	 * Get the definition of this form. Does not include the fields property
	 * 
	 * To obtain a full definition, use generateDefinition()
	 * 
	 * @see Form::generateDefinition()
	 * 
	 * @return array
	 */
	public function getDefinition() {
		return $this->a_definition;
	}
	
	/**
	 * Get the fields of this form
	 * 
	 * @return \Reef\Components\Field[]
	 */
	public function getFields() {
		return $this->a_fields;
	}
	
	/**
	 * Get the fields of this form that carry a value (i.e., are not static)
	 * 
	 * @return \Reef\Components\Field[]
	 */
	public function getValueFields() {
		$a_fields = $this->a_fields;
		foreach($a_fields as $i => $Field) {
			if($Field->getComponent()->getConfiguration()['category'] == 'static') {
				unset($a_fields[$i]);
			}
		}
		return $a_fields;
	}
	
	/**
	 * Get the fields of this form that carry a value (i.e., are not static),
	 * with the field names as key
	 * 
	 * @return \Reef\Components\Field[]
	 */
	public function getValueFieldsByName() {
		$a_fields = [];
		foreach($this->getValueFields() as $Field) {
			$a_fields[$Field->getDeclaration()['name']] = $Field;
		}
		return $a_fields;
	}
	
	/**
	 * Get the Reef object this Form belongs to
	 * 
	 * @return Reef
	 */
	public function getReef() {
		return $this->Reef;
	}
	
	/**
	 * Get the FormAssets object for this form
	 * 
	 * @return FormAssets
	 */
	public function getFormAssets() {
		if($this->FormAssets == null) {
			$this->FormAssets = new FormAssets($this);
		}
		
		return $this->FormAssets;
	}
	
	/**
	 * Get the id prefix
	 * 
	 * @return string
	 */
	public function getIdPfx() {
		return $this->s_idPfx;
	}
	
	/**
	 * Set the id prefix
	 * 
	 * @param string $s_idPfx The id prefix
	 */
	public function setIdPfx($s_idPfx) {
		$this->s_idPfx = $s_idPfx;
	}
	
	/**
	 * Get a new Creator object for this form
	 * 
	 * @return \Reef\Creator\Creator The new creator object
	 */
	public function newCreator() {
		return new \Reef\Creator\Creator($this);
	}
	
	/**
	 * Import a form definition from the given file
	 * 
	 * @param string $s_filename The file name
	 */
	public function importDefinitionFile(string $s_filename) {
		if(!file_exists($s_filename) || !is_readable($s_filename)) {
			throw new ResourceNotFoundException('Could not find file "'.$s_filename.'".');
		}
		
		$a_definition = Yaml::parseFile($s_filename);
		
		$this->importDefinition($a_definition);
	}
	
	/**
	 * Import a form definition from the given YAML string
	 * 
	 * @param string $s_definition The YAML string holding the definition
	 */
	public function importDefinitionString(string $s_definition) {
		$a_definition = Yaml::parse($s_definition);
		
		$this->importDefinition($a_definition);
	}
	
	/**
	 * Import a form definition from the given definition array
	 * 
	 * @param array $a_definition The definition array
	 */
	public function importDefinition(array $a_definition) {
		$this->Reef->checkDefinition($a_definition);
		$this->importValidatedDefinition($a_definition);
	}
	
	/**
	 * Import a form definition from the given definition array, omitting
	 * any validation. Should only be used when confident that the definition
	 * is valid.
	 * 
	 * @param array $a_definition The definition array
	 */
	public function importValidatedDefinition(array $a_definition) {
		$this->a_definition = $a_definition;
		unset($this->a_definition['fields']);
		
		$this->setFields($a_definition['fields']??[]);
	}
	
	/**
	 * Merge the form definition with the given partial definition
	 * 
	 * @param array $a_partialDefinition The partial definition array to merge into the current definition
	 */
	public function mergeDefinition(array $a_partialDefinition) {
		$this->a_definition = array_merge($this->a_definition, $a_partialDefinition);
	}
	
	/**
	 * Set the fields from a list of field declarations
	 * 
	 * @param Field[] $a_fields The list of field declarations (arrays)
	 */
	public function setFields(array $a_fields) {
		$Setup = $this->Reef->getSetup();
		
		$this->a_fields = [];
		foreach($a_fields as $s_id => $a_declaration) {
			$this->a_fields[$s_id] = $Setup->getField($a_declaration, $this);
		}
	}
	
	/**
	 * Generate a full form definition for this form
	 * 
	 * @return array The form definition
	 */
	public function generateDefinition() : array {
		$a_definition = $this->a_definition;
		
		$a_definition['fields'] = [];
		
		foreach($this->a_fields as $s_id => $Field) {
			$a_definition['fields'][$s_id] = $Field->getDeclaration();
		}
		
		return $a_definition;
	}
	
	/**
	 * Generate the form HTML
	 * 
	 * @param Submission|null $Submission The Submission to use as values, or null to create a default submission
	 * @param array $a_options Array of options, to choose from:
	 *  - main_var  (string)    The main variable name to use in the form. Defaults to 'reef_data'
	 * 
	 * @return string The form HTML
	 */
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
	
	/**
	 * Generate the form submission HTML
	 * 
	 * @param Submission $Submission The Submission to use as values
	 * @param array $a_options Array of options, to choose from:
	 * 
	 * @return string The form submission HTML
	 */
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
	
	/**
	 * Get a list of column titles in an overview
	 * 
	 * These values are used as headers in an overview or table. The method
	 * Submission::toOverviewColumns() generates an equally long list with
	 * values belonging to these headers, the keys in these arrays should match
	 * 
	 * @return string[] The overview column titles
	 */
	public function getOverviewColumns() {
		$a_overviewColumns = [];
		
		$a_fields = $this->getValueFields();
		foreach($a_fields as $Field) {
			$s_name = $Field->getDeclaration()['name'];
			$a_columns = $Field->getOverviewColumns();
			
			foreach($a_columns as $s_colName => $s_colTitle) {
				$a_overviewColumns[$s_name.'__'.$s_colName] = $s_colTitle;
			}
		}
		
		return $a_overviewColumns;
	}
	
	/**
	 * @inherit
	 */
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
	
	/**
	 * @inherit
	 */
	public function getCombinedLocaleSources($s_locale) {
		return $this->combineLocaleSources(
			$this->getOwnLocaleSource($s_locale),
			$this->Reef->getOwnLocaleSource($s_locale)
		);
	}
	
	/**
	 * @inherit
	 */
	protected function getDefaultLocale() {
		return $this->a_definition['default_locale']??$this->Reef->getOption('default_locale');
	}
	
	/**
	 * Replace the definition of this form with a new one
	 * 
	 * @param array $a_definition The new form definition array
	 * @param string[] $a_fieldRenames Mapping from field names in the old definition to field names in the new definition
	 */
	abstract public function updateDefinition(array $a_definition, array $a_fieldRenames = []);
	
	/**
	 * Check whether there will occur data loss when replacing the current definition with the given one
	 * 
	 * @param array $a_definition The new form definition array
	 * @param string[] $a_fieldRenames Mapping from field names in the old definition to field names in the new definition
	 * 
	 * @return string[] Mapping from field names to the Updater::DATALOSS_* constants
	 */
	abstract public function checkUpdateDataLoss(array $a_definition, array $a_fieldRenames = []);
	
	/**
	 * Return a new submission for this form
	 * 
	 * @return Submission
	 */
	abstract public function newSubmission();
	
}
