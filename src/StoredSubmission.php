<?php

namespace Reef;

use \Reef\Exception\BadMethodCallException;
use \Reef\Exception\StorageException;
use \Reef\Exception\ResourceNotFoundException;

class StoredSubmission extends Submission {
	
	private $i_submissionId;
	
	public function getSubmissionId() {
		return $this->i_submissionId;
	}
	
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
	
	public function saveAs(int $i_submissionId) {
		if($this->i_submissionId !== null) {
			throw new BadMethodCallException("Already saved submission");
		}
		
		$a_submission = $this->toFlat();
		$a_submission['_uuid'] = $this->getUUID();
		$this->i_submissionId = $this->Form->getSubmissionStorage()->insertAs($i_submissionId, $a_submission);
	}
	
	public function isNew() {
		return ($this->i_submissionId === null);
	}
	
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
	
	public function delete() {
		if($this->i_submissionId == null) {
			throw new BadMethodCallException("Unsaved submission.");
		}
		$this->Form->getSubmissionStorage()->delete($this->i_submissionId);
		$this->getForm()->getReef()->getDataStore()->getFilesystem()->removeContextDir($this);
	}
	
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
