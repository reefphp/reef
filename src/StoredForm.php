<?php

namespace Reef;

use \Reef\Exception\ResourceNotFoundException;
use \Reef\Exception\BadMethodCallException;
use \Reef\Exception\ValidationException;
use Symfony\Component\Yaml\Yaml;

class StoredForm extends Form {
	
	private $SubmissionStorage;
	private $i_formId;
	
	public function getFormId() {
		return $this->i_formId;
	}
	
	public function getStorageName() {
		return $this->a_definition['storage_name']??null;
	}
	
	public function setStorageName($s_newStorageName) {
		$this->Reef->getDataStore()->changeSubmissionStorageName($this, $s_newStorageName);
		$this->a_definition['storage_name'] = $s_newStorageName;
	}
	
	public function getSubmissionStorage() {
		if(empty($this->a_definition['storage_name']??null)) {
			return null;
		}
		
		if(empty($this->SubmissionStorage)) {
			$this->SubmissionStorage = $this->Reef->getSubmissionStorage($this);
		}
		
		return $this->SubmissionStorage;
	}
	
	public function newDefinitionFromFile(string $s_filename) {
		if(!file_exists($s_filename) || !is_readable($s_filename)) {
			throw new ResourceNotFoundException('Could not find file "'.$s_filename.'".');
		}
		
		$a_definition = Yaml::parseFile($s_filename);
		
		$this->newDefinition($a_definition);
	}
	
	public function newDefinition(array $a_definition) {
		if(empty($a_definition['storage_name'])) {
			throw new ValidationException("Missing storage_name");
		}
		
		$this->a_definition['storage_name'] = $a_definition['storage_name'];
		$this->updateDefinition($a_definition);
	}
	
	public function updateDefinition(array $a_definition, array $a_fieldRenames = []) {
		$Form2 = clone $this;
		$Form2->importDefinition($a_definition);
		
		$Updater = new Updater();
		$Updater->update($this, $Form2, $a_fieldRenames);
	}
	
	public function checkUpdateDataLoss(array $a_definition, array $a_fieldRenames = []) {
		$Form2 = clone $this;
		$Form2->importDefinition($a_definition);
		
		$Updater = new Updater();
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
	
	public function load(int $i_formId) {
		$a_result = $this->Reef->getFormStorage()->get($i_formId);
		if($a_result === null) {
			throw new ResourceNotFoundException('Could not find form with id "'.$i_formId.'"');
		}
		$this->importValidatedDefinition(json_decode($a_result['definition'], true));
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
	
	public function newSubmission() {
		return new StoredSubmission($this);
	}
	
}
