<?php

namespace Reef\Form;

use \Reef\Reef;
use \Reef\Submission\Submission;
use \Reef\Submission\StoredSubmission;
use \Reef\SubmissionOverview;
use \Reef\Exception\BadMethodCallException;

/**
 * An AbstractStoredForm is a Form that is persisted in the database, it can be either persistable (StoredForm) or inpersistable (InpersistableStoredForm)
 */
abstract class AbstractStoredForm extends AbstractStorableForm {
	
	/**
	 * The submission storage of this form
	 * @type Storage
	 */
	protected $SubmissionStorage;
	
	/**
	 * The form id of this form
	 * @type ?int
	 */
	protected $i_formId;
	
	/**
	 * Constructor
	 * 
	 * @param Reef $Reef The Reef object this Form belongs to
	 * @param array $a_definition The form definition
	 * @param ?int $i_formId The form id, or null for a new form
	 * @param ?string $s_uuid The uuid, may be null to generate one
	 */
	public function __construct(Reef $Reef, array $a_definition, ?int $i_formId, ?string $s_uuid) {
		if(!empty($a_definition['fields']) && $i_formId === null) {
			throw new BadMethodCallException('Cannot initiate a new stored form with fields defined. Please use updateDefinition() or start from TempStortoredForm.');
		}
		
		$this->s_uuid = $s_uuid;
		
		parent::__construct($Reef, $a_definition);
		
		$this->i_formId = $i_formId;
	}
	
	/**
	 * Get the form id of this form
	 * @return ?int The form id, or null if this form is not yet saved
	 */
	public function getFormId() {
		return $this->i_formId;
	}
	
	/**
	 * Create the submission storage in the database if it does not exist yet
	 */
	public function createSubmissionStorageIfNotExists() {
		if(!$this->Reef->getDataStore()->hasSubmissionStorage($this->getStorageName())) {
			$this->Reef->getDataStore()->createSubmissionStorage($this);
		}
	}
	
	/**
	 * Get the submission storage
	 * @return Storage The submission storage
	 * @throws BadMethodCallException If the storage name is empty
	 */
	public function getSubmissionStorage() {
		if(empty($this->a_definition['storage_name']??null)) {
			throw new BadMethodCallException('Storage name not set');
		}
		
		if(empty($this->SubmissionStorage)) {
			$this->SubmissionStorage = $this->Reef->getSubmissionStorage($this);
		}
		
		return $this->SubmissionStorage;
	}
	
	/**
	 * @inherit
	 */
	public function checkUpdateDataLoss(array $a_definition, array $a_fieldRenames = []) {
		$Form2 = $this->Reef->newTempStorableForm();
		$Form2->setDefinition($a_definition);
		
		// If there are no rows, there will be no data loss
		if($this->getNumSubmissions() === 0) {
			return [];
		}
		
		$Updater = new \Reef\Updater();
		return $Updater->determineUpdateDataLoss($this, $Form2, $a_fieldRenames);
	}
	
	/**
	 * Get all submission ids of this form
	 * @return int[]
	 */
	public function getSubmissionIds() {
		return ($this->i_formId === null) ? [] : $this->getSubmissionStorage()->list();
	}
	
	/**
	 * Get the number of submissions of this form
	 * @return int
	 */
	public function getNumSubmissions() : int {
		return ($this->i_formId === null) ? 0 : $this->getSubmissionStorage()->count();
	}
	
	/**
	 * Get the submission with the specified submission id
	 * @param int $i_submissionId The submission id
	 * @return Submission
	 */
	public function getSubmission(int $i_submissionId) : Submission {
		$Submission = $this->newSubmission();
		
		$Submission->load($i_submissionId);
		
		return $Submission;
	}
	
	/**
	 * Get the submission with the specified submission uuid
	 * @param string $s_submissionUUID The submission uuid
	 * @return Submission
	 */
	public function getSubmissionByUUID(string $s_submissionUUID) : Submission {
		$Submission = $this->newSubmission();
		
		$Submission->loadByUUID($s_submissionUUID);
		
		return $Submission;
	}
	
	/**
	 * Create a new submission overview instance for this form
	 * @return SubmissionOverview
	 */
	public function newSubmissionOverview() {
		return new SubmissionOverview($this);
	}
	
	/**
	 * @inherit
	 */
	public function newSubmission() {
		return new StoredSubmission($this);
	}
	
}
