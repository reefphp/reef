<?php

namespace Reef\Submission;

use \Reef\Exception\StorageException;
use \Reef\Exception\ResourceNotFoundException;

/**
 * An abstract stored submission is a submission that is stored in the database, either persistable or nonpersistable
 */
abstract class AbstractStoredSubmission extends Submission {
	
	/**
	 * The id of this submission
	 * @type int
	 */
	protected $i_submissionId;
	
	/**
	 * Get the id of this submission
	 * @return int
	 */
	public function getSubmissionId() {
		return $this->i_submissionId;
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
}
