<?php

namespace Reef;

use \Reef\Exception\BadMethodCallException;

class StoredSubmission extends Submission {
	
	private $i_submissionId;
	
	public function getSubmissionId() {
		return $this->i_submissionId;
	}
	
	public function save() {
		$a_submission = $this->toFlat();
		
		if($this->i_submissionId == null) {
			$this->i_submissionId = $this->Form->getSubmissionStorage()->insert($a_submission);
		}
		else {
			$this->Form->getSubmissionStorage()->update($this->i_submissionId, $a_submission);
		}
	}
	
	public function saveAs(int $i_submissionId) {
		if($this->i_submissionId !== null) {
			throw new BadMethodCallException("Already saved submission");
		}
		
		$a_submission = $this->toFlat();
		$this->i_submissionId = $this->Form->getSubmissionStorage()->insertAs($i_submissionId, $a_submission);
	}
	
	public function isNew() {
		return ($this->i_submissionId === null);
	}
	
	public function load(int $i_submissionId) {
		$this->fromFlat($this->Form->getSubmissionStorage()->get($i_submissionId));
		$this->i_submissionId = $i_submissionId;
	}
	
	public function delete() {
		if($this->i_submissionId == null) {
			throw new BadMethodCallException("Unsaved submission.");
		}
		$this->Form->getSubmissionStorage()->delete($this->i_submissionId);
	}
}
