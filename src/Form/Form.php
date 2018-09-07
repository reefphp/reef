<?php

namespace Reef\Form;

use \Reef\Reef;
use \Reef\Assets\FormAssets;
use \Reef\Submission\Submission;
use \Reef\Locale\Trait_FormLocale;
use \Reef\Exception\ResourceNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * General Form class
 * 
 * Holds general form functionality. A form is defined by a set of fields along with additional
 * configuration, all defined in the form definition.
 */
abstract class Form {
	
	use Trait_FormLocale;
	
	/**
	 * The Reef object this Form belongs to
	 * 
	 * @var Reef
	 */
	protected $Reef;
	
	/**
	 * Unique id
	 * 
	 * @var string
	 */
	protected $s_uuid;
	
	/**
	 * The FormAssets object used by this form
	 * 
	 * @var FormAssets
	 */
	protected $FormAssets;
	
	/**
	 * The ConditionEvaluator object used by this form
	 * 
	 * @var ConditionEvaluator
	 */
	protected $ConditionEvaluator;
	
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
	 * @param array $a_definition The form definition to start from
	 */
	public function __construct(Reef $Reef, array $a_definition) {
		$this->Reef = $Reef;
		$this->s_uuid = $this->s_uuid ?? \Reef\unique_id();
		$this->s_idPfx = \Reef\unique_id();
		
		$this->setDefinition($a_definition);
	}
	
	/**
	 * Get the uuid of this form
	 * 
	 * @return string
	 */
	public function getUUID() {
		return $this->s_uuid;
	}
	
	/**
	 * Get the partial definition of this form: the entire definition excluding the fields property
	 * 
	 * To obtain the full definition, use getDefinition()
	 * 
	 * @see Form::getDefinition()
	 * 
	 * @return array
	 */
	public function getPartialDefinition() {
		return $this->a_definition;
	}
	
	/**
	 * Set the definition of this form
	 * 
	 * @param array $a_definition The definition
	 */
	protected function setDefinition(array $a_definition) {
		$this->a_definition = $a_definition;
		unset($this->a_definition['fields']);
		$this->setFields($a_definition['fields']??[]);
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
	 * Get the ConditionEvaluator object for this form
	 * 
	 * @return \Reef\ConditionEvaluator
	 */
	public function getConditionEvaluator() {
		if($this->ConditionEvaluator == null) {
			$this->ConditionEvaluator = new \Reef\ConditionEvaluator($this);
		}
		
		return $this->ConditionEvaluator;
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
	 * Duplicate this form into a temporary form
	 * 
	 * @return TempForm
	 */
	public function tempDuplicate() {
		$Form = $this->Reef->newValidTempForm($this->getDefinition());
		$Form->s_uuid = $this->s_uuid;
		return $Form;
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
		
		$this->ConditionEvaluator = null;
	}
	
	/**
	 * Generate a full form definition for this form
	 * 
	 * If you are not interested in the fields property, consider using getPartialDefinition() for performance
	 * 
	 * @see getPartialDefinition()
	 * 
	 * @return array The form definition
	 */
	public function getDefinition() : array {
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
		$a_data['layout'] = $Layout->view($this->a_definition['layout'][$Layout->getName()] ?? []);
		$a_data['internal_request_url'] = $this->Reef->getOption('internal_request_url');
		$a_data['byte_base'] = $this->Reef->getOption('byte_base');
		$a_data['form_uuid'] = $this->getUUID();
		
		$Mustache = $this->Reef->newMustache();
		$Mustache->addHelper('form_idpfx', $this->s_idPfx);
		$Mustache->addHelper('main_var', $a_data['main_var']);
		$Mustache->addHelper('layout', $a_data['layout']);
		
		$ExtensionCollection = $this->Reef->getExtensionCollection();
		
		foreach($this->a_fields as $Field) {
			$Mustache->setLoader($Field->getComponent()->getTemplateLoader($Layout->getName(), 'form'));
			$Template = $Mustache->loadTemplate('form');
			$Value = ($Field->getComponent()->getConfiguration()['category'] == 'static') ? $Field->newValue($Submission) : $Submission->getFieldValue($Field->getDeclaration()['name']);
			$a_vars = $Field->view_form($Value, \Reef\array_subset($a_options, ['locale']));
			
			/**
			 * Event before a field form template is rendered
			 * @event reef.before_field_form_render
			 * @var array view_vars The variables passed to the view
			 * @var Field field The field object
			 * @var FieldValue field_value The field value object
			 */
			$ExtensionCollection->event('reef.before_field_form_render', [
				'view_vars' => &$a_vars,
				'field' => $Field,
				'field_value' => $Value,
			]);
			
			$s_html = $Template->render([
				'field' => $a_vars,
			]);
			
			$a_fields[] = [
				'html' => $s_html,
			];
		}
		
		$Mustache->setLoader(new \Reef\Mustache\FilesystemLoader($this->Reef, Reef::getDir() . 'src/'));
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
		$a_data['layout'] = $Layout->view($this->a_definition['layout'][$Layout->getName()] ?? []);
		$a_data['internal_request_url'] = $this->Reef->getOption('internal_request_url');
		
		$Mustache = $this->Reef->newMustache();
		$Mustache->addHelper('form_idpfx', $this->s_idPfx);
		$Mustache->addHelper('layout', $a_data['layout']);
		
		$ExtensionCollection = $this->Reef->getExtensionCollection();
		
		foreach($this->a_fields as $Field) {
			$Mustache->setLoader($Field->getComponent()->getTemplateLoader($Layout->getName(), 'submission'));
			$Template = $Mustache->loadTemplate('submission');
			$Value = ($Field->getComponent()->getConfiguration()['category'] == 'static') ? $Field->newValue($Submission) : $Submission->getFieldValue($Field->getDeclaration()['name']);
			$a_vars = $Field->view_submission($Value, \Reef\array_subset($a_options, ['locale']));
			
			/**
			 * Event before a field submission template is rendered
			 * @event reef.before_field_submission_render
			 * @var array view_vars The variables passed to the view
			 * @var Field field The field object
			 * @var FieldValue field_value The field value object
			 */
			$ExtensionCollection->event('reef.before_field_submission_render', [
				'view_vars' => &$a_vars,
				'field' => $Field,
				'field_value' => $Value,
			]);
			
			$s_html = $Template->render([
				'field' => $a_vars,
			]);
			
			$a_fields[] = [
				'html' => $s_html,
			];
		}
		
		$Mustache->setLoader(new \Reef\Mustache\FilesystemLoader($this->Reef, Reef::getDir() . 'src/'));
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
	 * Perform an internal request
	 * @param string $s_requestHash The hash containing the action to perform
	 * @param array $a_options Array with options
	 */
	public function internalRequest(string $s_requestHash, array $a_options = []) {
		$a_requestHash = explode(':', $s_requestHash);
		if(count($a_requestHash) == 1) {
			throw new \Reef\Exception\InvalidArgumentException("Illegal request hash");
		}
		
		if($a_requestHash[0] == 'submission') {
			if($a_requestHash[1] != 'temp' && $this instanceof StoredForm) {
				$Submission = $this->getSubmissionByUUID($a_requestHash[1]);
			}
			else {
				$Submission = $a_options['submission'] ?? $this->newSubmission();
			}
			if(isset($a_options['submission_check'])) {
				$a_options['submission_check']($Submission);
			}
			
			array_shift($a_requestHash);
			array_shift($a_requestHash);
			
			return $Submission->internalRequest(implode(':', $a_requestHash), $a_options);
		}
		
		if($a_requestHash[0] == 'field') {
			$a_fields = $this->getValueFieldsByName();
			
			if(!isset($a_fields[$a_requestHash[1]])) {
				throw new \Reef\Exception\InvalidArgumentException("Could not find field '".$a_requestHash[1]."'");
			}
			
			$Field = $a_fields[$a_requestHash[1]];
			
			array_shift($a_requestHash);
			array_shift($a_requestHash);
			
			return $Field->internalRequest(implode(':', $a_requestHash), $a_options);
		}
		
		throw new \Reef\Exception\InvalidArgumentException('Invalid request hash');
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
