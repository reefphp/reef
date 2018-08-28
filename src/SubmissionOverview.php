<?php

namespace Reef;

use \Reef\Form\StoredForm;
use \Reef\Exception\InvalidArgumentException;

class SubmissionOverview {
	
	protected $Form;
	protected $a_config = [
		'delimiter' => ',',
		'enclosure' => '"',
		'escape_char' => '\\',
		'raw' => false,
		'callback_head' => null,
		'callback_row' => null,
	];
	
	public function __construct(StoredForm $Form) {
		$this->Form = $Form;
	}
	
	public function set(string $s_key, $m_value) {
		if(!array_key_exists($s_key, $this->a_config)) {
			throw new InvalidArgumentException('Invalid config key "'.$s_key.'"');
		}
		
		if($s_key == 'raw') {
			$m_value = (bool)$m_value;
		}
		
		$this->a_config[$s_key] = $m_value;
		
		return $this;
	}
	
	public function getHead() : array {
		if($this->a_config['raw']) {
			$a_head = null;
			foreach($this->Form->getSubmissionStorage()->generator(0, 1) as $a_row) {
				$a_head = array_keys($a_row);
				break;
			}
			
			if($a_head === null) {
				// Fallback when 0 rows
				$a_head = $this->Form->getSubmissionStorage()->getColumns();
			}
		}
		else {
			$a_head = $this->Form->getOverviewColumns();
			array_unshift($a_head, $this->Form->getReef()->trans('overview_submission_id'));
		}
		
		if(is_callable($this->a_config['callback_head'])) {
			$this->a_config['callback_head']($a_head);
		}
		
		return $a_head;
	}
	
	public function getTable(int $i_offset = 0, int $i_num = -1) : array {
		return iterator_to_array($this->getGenerator($i_offset, $i_num));
	}
	
	public function getGenerator(int $i_offset = 0, int $i_num = -1) : iterable {
		$b_callback = is_callable($this->a_config['callback_row']);
		
		if($this->a_config['raw']) {
			foreach($this->Form->getSubmissionStorage()->generator($i_offset, $i_num) as $a_row) {
				if($b_callback) {
					$this->a_config['callback_row']($a_row);
				}
				
				yield $a_row;
			}
		}
		else {
			$a_submissionIds = $this->Form->getSubmissionIds();
			$a_submissionIds = array_slice($a_submissionIds, $i_offset, $i_num >= 0 ? $i_num : null);
			foreach($a_submissionIds as $i_submissionId) {
				$Submission = $this->Form->getSubmission($i_submissionId);
				
				$a_row = $Submission->toOverviewColumns();
				array_unshift($a_row, $Submission->getSubmissionId());
				
				if($b_callback) {
					$this->a_config['callback_row']($a_row);
				}
				
				yield $a_row;
			}
		}
	}
	
	public function streamCSV(string $s_filename = null) {
		if(empty($s_filename)) {
			$s_filename = 'data.csv';
		}
		
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=".$s_filename);
		header("Pragma: no-cache");
		header("Expires: 0");
		
		$fp = fopen("php://output", "w");
		$this->fCSV($fp);
		fclose($fp);
		exit;
	}
	
	public function CSV() : string {
		// Write CSV
		$fp = fopen('php://temp', 'r+');
		$this->fCSV($fp);
		
		// Read CSV
		rewind($fp);
		$s_csv = stream_get_contents($fp);
		
		// Return CSV
		fclose($fp);
		return $s_csv;
	}
	
	public function fCSV($fp) {
		
		$a_head = $this->getHead();
		fputcsv($fp, array_values($a_head), $this->a_config['delimiter'], $this->a_config['enclosure'], $this->a_config['escape_char']);
		
		foreach($this->getGenerator() as $a_row) {
			fputcsv($fp, array_values($a_row), $this->a_config['delimiter'], $this->a_config['enclosure'], $this->a_config['escape_char']);
		}
		
	}
	
}
