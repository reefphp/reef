<?php

namespace Reef\Submission;

use \Reef\Exception\BadMethodCallException;

/**
 * A nonpersistable stored submission is a submission that is stored in the database and cannot be modified
 */
class NonpersistableStoredSubmission extends AbstractStoredSubmission {
	
	/**
	 * Copy submission data from an other submission
	 * @param AbstractStoredSubmission $SourceSubmission The submission to copy from
	 * @throws BadMethodCallException If the given submission does not belong to the same form as this submission is initiated with
	 */
	public function copyFrom(AbstractStoredSubmission $SourceSubmission) {
		if($this->getForm()->getFormId() != $SourceSubmission->getForm()->getFormId()) {
			throw new BadMethodCallException('Submissions should belong to the same form');
		}
		
		$this->fromStructured($SourceSubmission->toStructured());
		$this->i_submissionId = $SourceSubmission->i_submissionId;
		$this->s_uuid = $SourceSubmission->s_uuid;
	}
	
}
