<?php

namespace Reef\Submission;

use \Reef\Exception\BadMethodCallException;

/**
 * A stored submission is a submission that is stored in the database
 */
class StoredSubmission extends AbstractStoredSubmission {
	
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
			$this->getForm()->getReef()->getDataStore()->ensureTransaction(function() use ($a_userInput, $a_options, $Exception) {
				
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
