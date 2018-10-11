<?php

namespace Reef\Form;

use \Reef\Submission\AbstractStoredSubmission;
use \Reef\Submission\NonpersistableStoredSubmission;
use \Reef\Exception\BadMethodCallException;

/**
 * A NonpersistableStoredForm is a Form that is persisted in the database, that does not allow saving to the database
 */
class NonpersistableStoredForm extends AbstractStoredForm {
	
	/**
	 * @inherit
	 */
	public function setStorageName($s_newStorageName) {
		throw new BadMethodCallException("Cannot set storage name on nonpersistable object");
	}
	
	/**
	 * Update the definition of this form
	 * @inherit
	 */
	public function updateDefinition(array $a_definition, array $a_fieldRenames = []) {
		$this->setDefinition($a_definition);
	}
	
	/**
	 * Create a new nonpersistable submission object from an existing stored submission
	 * @param AbstractStoredSubmission $SourceSubmission The submission to copy from
	 */
	public function newNonpersistableSubmission(AbstractStoredSubmission $SourceSubmission) {
		$NewSubmission = new NonpersistableStoredSubmission($this);
		$NewSubmission->copyFrom($SourceSubmission);
		return $NewSubmission;
	}
	
}
