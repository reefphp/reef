<?php

namespace Reef;

use \Reef\Exception\IOException;
use Symfony\Component\Yaml\Yaml;

class Submission {
	
	private $Form;
	
	private $i_submissionId;
	private $a_componentValues = [];
	
	/**
	 * Constructor
	 */
	public function __construct(Form $Form) {
		$this->Form = $Form;
	}
	
	public function getSubmissionId() {
		return $this->i_submissionId;
	}
	
	public function emptySubmission() {
		$a_components = $this->Form->getComponents();
		$this->a_componentValues = [];
		foreach($a_components as $Component) {
			$this->a_componentValues[$Component->getConfig()['name']] = $Component->newValue();
		}
	}
	
	public function fromUserInput($a_input) {
		$a_components = $this->Form->getComponents();
		$this->a_componentValues = [];
		foreach($a_components as $Component) {
			$s_name = $Component->getConfig()['name'];
			$this->a_componentValues[$s_name] = $Component->newValue();
			$this->a_componentValues[$s_name]->fromUserInput($a_input[$s_name] ?? null);
		}
	}
	
	public function validate() : bool {
		$b_valid = true;
		
		$a_components = $this->Form->getComponents();
		foreach($a_components as $Component) {
			$s_name = $Component->getConfig()['name'];
			$b_valid = $this->a_componentValues[$s_name]->validate() && $b_valid;
		}
		
		return $b_valid;
	}
	
	public function getErrors() : array {
		$a_errors = [];
		
		$a_components = $this->Form->getComponents();
		foreach($a_components as $Component) {
			$s_name = $Component->getConfig()['name'];
			$a_componentErrors = $this->a_componentValues[$s_name]->getErrors();
			if(!empty($a_componentErrors)) {
				$a_errors[$s_name] = $a_componentErrors;
			}
		}
		
		return $a_errors;
	}
	
	public function getComponentValue($s_name) {
		if(!isset($this->a_componentValues[$s_name])) {
			throw new IOException("Could not find value for component '".$s_name."'.");
		}
		
		return $this->a_componentValues[$s_name];
	}
	
	public function toFlat() {
		$a_flat = [];
		
		$a_components = $this->Form->getComponents();
		foreach($a_components as $Component) {
			$s_name = $Component->getConfig()['name'];
			$a_flatComponent = $this->a_componentValues[$s_name]->toFlat();
			
			$a_prefixedFlatComponent = [];
			
			foreach($a_flatComponent as $s_subfieldName => $m_value) {
				$a_prefixedFlatComponent[$s_name.'__'.$s_subfieldName] = $m_value;
			}
			
			$a_flat = array_merge($a_flat, $a_prefixedFlatComponent);
		}
		
		return $a_flat;
	}
	
	public function fromFlat($a_flat) {
		$a_grouped = [];
		
		foreach($a_flat as $s_key => $m_value) {
			$i_pos = strrpos($s_key, '__');
			$a_grouped[substr($s_key, 0, $i_pos)][substr($s_key, $i_pos+2)] = $m_value;
		}
		
		$a_components = $this->Form->getComponents();
		$this->a_componentValues = [];
		foreach($a_components as $Component) {
			$s_name = $Component->getConfig()['name'];
			$this->a_componentValues[$s_name] = $Component->newValue();
			$this->a_componentValues[$s_name]->fromFlat($a_grouped[$s_name] ?? null);
		}
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
	
	public function isNew() {
		return ($this->i_submissionId === null);
	}
	
	public function load(int $i_submissionId) {
		$this->fromFlat($this->Form->getSubmissionStorage()->get($i_submissionId));
		$this->i_submissionId = $i_submissionId;
	}
	
	public function delete() {
		if($this->i_submissionId == null) {
			throw new \Exception("Unsaved submission.");
		}
		$this->Form->getSubmissionStorage()->delete($this->i_submissionId);
	}
}
