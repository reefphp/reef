<?php

namespace Reef\Submission;

use \Reef\Exception\BadMethodCallException;
use \Reef\Exception\StorageException;
use \Reef\Exception\ResourceNotFoundException;

/**
 * A stored submission is a submission that is stored in the database
 */
class StoredSubmission extends Submission {
	
	/**
	 * The id of this submission
	 * @type int
	 */
	private $i_submissionId;
	
	/**
	 * Get the id of this submission
	 * @return int
	 */
	public function getSubmissionId() {
		return $this->i_submissionId;
	}
	
	/**
	 * Save this submission in the database
	 */
	public function save() {
		$a_submission = $this->toFlat();
		$a_submission['_uuid'] = $this->getUUID();
		
		if($this->i_submissionId == null) {
			$this->i_submissionId = $this->Form->getSubmissionStorage()->insert($a_submission);
		}
		else {
			$this->Form->getSubmissionStorage()->update($this->i_submissionId, $a_submission);
		}
	}
	
	/**
	 * Determine whether this submission is a new submission
	 * @return bool True if this submission is new, i.e. if the submission id is `null`
	 */
	public function isNew() {
		return ($this->i_submissionId === null);
	}
	
	/**
	 * Load a submission from the database
	 * @param int $i_submissionId The submission id to load
	 * @throws ResourceNotFoundException If the specified id does not exist
	 */
	public function load(int $i_submissionId) {
		try {
			$a_submission = $this->Form->getSubmissionStorage()->get($i_submissionId);
		}
		catch(StorageException $e) {
			throw new ResourceNotFoundException('Could not find submission with id "'.$i_submissionId.'"', null, $e);
		}
		
		$this->fromFlat($a_submission);
		$this->i_submissionId = $i_submissionId;
		$this->s_uuid = $a_submission['_uuid'];
	}
	
	/**
	 * Load a submission from the database
	 * @param string $s_submissionUUID The submission uuid to load
	 * @throws ResourceNotFoundException If the specified uuid does not exist
	 */
	public function loadByUUID(string $s_submissionUUID) {
		try {
			$a_submission = $this->Form->getSubmissionStorage()->getByUUID($s_submissionUUID);
		}
		catch(StorageException $e) {
			throw new ResourceNotFoundException('Could not find submission with UUID "'.$s_submissionUUID.'"', null, $e);
		}
		
		$this->fromFlat($a_submission);
		$this->i_submissionId = $a_submission['_entry_id'];
		$this->s_uuid = $a_submission['_uuid'];
	}
	
	/**
	 * Delete this submission
	 * @throws BadMethodCallException If this submission is not saved
	 */
	public function delete() {
		if($this->i_submissionId == null) {
			throw new BadMethodCallException("Unsaved submission.");
		}
		$this->Form->getSubmissionStorage()->delete($this->i_submissionId);
		$this->getForm()->getReef()->getDataStore()->getFilesystem()->removeContextDir($this);
	}
	
	/**
	 * Wrapper function for importing, validating and saving user input all inside one transaction
	 * @param ?array $a_userInput The user input
	 * @param array $a_options Array of options, to choose from:
	 *  - validate_before       function  Callback called after data import and before validation
	 *  - validate_after        function  Callback called after validation and before saving
	 * @return True if the data was valid, in which case the data has been saved into the database.
	 *         False if the data was invalid, in which case the errors can be retrieved with getErrors()
	 */
	public function processUserInput(?array $a_userInput, array $a_options = []) : bool {
		$Exception = new class extends \Exception {};
		
		try {
			$this->getForm()->getReef()->getDataStore()->ensureTransaction(function() use ($a_userInput, $Exception) {
				
				$this->fromUserInput($a_userInput);
				
				if(isset($a_options['validate_before'])) {
					$a_options['validate_before']();
				}
				
				if(!$this->validate()) {
					throw new $Exception();
				}
				
				if(isset($a_options['validate_after'])) {
					$a_options['validate_after']();
				}
				
				$this->save();
			});
			
			return true;
		}
		catch(\Exception $e) {
			if($e instanceof $Exception) {
				return false;
			}
			
			throw $e;
		}
	}
}
