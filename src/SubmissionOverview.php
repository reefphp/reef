<?php

namespace Reef;

use \Reef\Exception\InvalidArgumentException;

class SubmissionOverview {
	
	protected $Form;
	protected $a_config = [
		'delimiter' => ',',
		'enclosure' => '"',
		'escape_char' => '\\',
		'raw' => false,
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
	
	public function getTable(int $i_offset = 0, int $i_num = -1) : array {
		return $this->Form->getSubmissionStorage()->table($i_offset, $i_num);
	}
	
	public function getGenerator(int $i_offset = 0, int $i_num = -1) : iterable {
		yield from $this->Form->getSubmissionStorage()->generator($i_offset, $i_num);
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
		if($this->a_config['raw']) {
			$this->fCSV_raw($fp);
		}
		else {
			$this->fCSV_value($fp);
		}
	}
	
	protected function fCSV_raw($fp) {
		
		$b_first = true;
		
		foreach($this->Form->getSubmissionStorage()->generator() as $a_row) {
			if($b_first) {
				fputcsv($fp, array_keys($a_row), $this->a_config['delimiter'], $this->a_config['enclosure'], $this->a_config['escape_char']);
				$b_first = false;
			}
			
			fputcsv($fp, array_values($a_row), $this->a_config['delimiter'], $this->a_config['enclosure'], $this->a_config['escape_char']);
		}
		
	}
	
	protected function fCSV_value($fp) {
		
		$a_head = $this->Form->getOverviewColumns();
		array_unshift($a_head, 'Submission id');
		
		fputcsv($fp, $a_head, $this->a_config['delimiter'], $this->a_config['enclosure'], $this->a_config['escape_char']);
		
		foreach($this->Form->getSubmissionIds() as $i_submissionId) {
			
			$Submission = $this->Form->getSubmission($i_submissionId);
			
			$a_row = $Submission->toOverviewColumns();
			array_unshift($a_row, $Submission->getSubmissionId());
			
			fputcsv($fp, $a_row, $this->a_config['delimiter'], $this->a_config['enclosure'], $this->a_config['escape_char']);
		}
		
	}
	
}
