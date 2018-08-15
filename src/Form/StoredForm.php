<?php

namespace Reef\Form;

use \Reef\Reef;
use \Reef\Submission;
use \Reef\StoredSubmission;
use \Reef\SubmissionOverview;
use \Reef\Exception\BadMethodCallException;

class StoredForm extends AbstractStoredForm {
	
	private $SubmissionStorage;
	private $i_formId;
	
	/**
	 * Constructor
	 * 
	 * @param Reef $Reef The Reef object this Form belongs to
	 * @param array $a_definition The form definition
	 * @param ?int $i_formId The form id, or null for a new form
	 */
	public function __construct(Reef $Reef, array $a_definition, ?int $i_formId) {
		if(!empty($a_definition['fields']) && $i_formId === null) {
			throw new BadMethodCallException('Cannot initiate a new stored form with fields defined. Please use updateDefinition() or start from TempStortoredForm.');
		}
		
		parent::__construct($Reef, $a_definition);
		
		$this->i_formId = $i_formId;
	}
	
	public function getFormId() {
		return $this->i_formId;
	}
	
	public function setStorageName($s_newStorageName) {
		if(!empty($this->a_definition['storage_name'])) {
			$this->Reef->getDataStore()->changeSubmissionStorageName($this, $s_newStorageName);
		}
		parent::setStorageName($s_newStorageName);
	}
	
	public function createSubmissionStorageIfNotExists() {
		if(!$this->Reef->getDataStore()->hasSubmissionStorage($this->getStorageName())) {
			$this->Reef->getDataStore()->createSubmissionStorage($this);
		}
	}
	
	public function getSubmissionStorage() {
		if(empty($this->a_definition['storage_name']??null)) {
			throw new BadMethodCallException('Storage name not set');
		}
		
		if(empty($this->SubmissionStorage)) {
			$this->SubmissionStorage = $this->Reef->getSubmissionStorage($this);
		}
		
		return $this->SubmissionStorage;
	}
	
	public function updateDefinition(array $a_definition, array $a_fieldRenames = []) {
		$Form2 = $this->Reef->newTempStoredForm();
		$Form2->setDefinition($a_definition);
		
		$Updater = new \Reef\Updater();
		$Updater->update($this, $Form2, $a_fieldRenames);
	}
	
	public function checkUpdateDataLoss(array $a_definition, array $a_fieldRenames = []) {
		$Form2 = $this->Reef->newTempStoredForm();
		$Form2->setDefinition($a_definition);
		
		// If there are no rows, there will be no data loss
		if($this->getNumSubmissions() === 0) {
			return [];
		}
		
		$Updater = new \Reef\Updater();
		return $Updater->determineUpdateDataLoss($this, $Form2, $a_fieldRenames);
	}
	
	public function save() {
		$a_definition = $this->generateDefinition();
		
		if($this->i_formId == null) {
			$this->i_formId = $this->Reef->getFormStorage()->insert(['definition' => json_encode($a_definition)]);
		}
		else {
			$this->Reef->getFormStorage()->update($this->i_formId, ['definition' => json_encode($a_definition)]);
		}
	}
	
	public function saveAs(int $i_formId) {
		if($this->i_formId !== null) {
			throw new BadMethodCallException("Already saved form");
		}
		
		$a_definition = $this->generateDefinition();
		$this->i_formId = $this->Reef->getFormStorage()->insertAs($i_formId, ['definition' => json_encode($a_definition)]);
	}
	
	public function delete() {
		$this->Reef->getDataStore()->deleteSubmissionStorageIfExists($this);
		
		if($this->i_formId !== null) {
			$this->Reef->getFormStorage()->delete($this->i_formId);
		}
	}
	
	public function getSubmissionIds() {
		return ($this->i_formId === null) ? [] : $this->getSubmissionStorage()->list();
	}
	
	public function getNumSubmissions() : int {
		return ($this->i_formId === null) ? 0 : $this->getSubmissionStorage()->count();
	}
	
	public function getSubmission(int $i_submissionId) : Submission {
		$Submission = $this->newSubmission();
		
		$Submission->load($i_submissionId);
		
		return $Submission;
	}
	
	public function newSubmissionOverview() {
		return new SubmissionOverview($this);
	}
	
	public function newSubmission() {
		return new StoredSubmission($this);
	}
	
}
