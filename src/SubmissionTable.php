<?php

namespace Reef;

use \Reef\Exception\BadMethodCallException;

class SubmissionTable {
	
	protected $Form;
	
	public function __construct(StoredForm $Form) {
		$this->Form = $Form;
	}
	
	public function getTable() : array {
		return $this->Form->getSubmissionStorage()->table();
	}
	
	public function getGenerator() : iterable {
		yield from $this->Form->getSubmissionStorage()->generator();
	}
	
	public function streamCSV(string $s_filename = null, string $s_delimiter = ',', string $s_enclosure = '"', string $s_escape_char = '\\') {
		if(empty($s_filename)) {
			$s_filename = 'data.csv';
		}
		
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=".$s_filename);
		header("Pragma: no-cache");
		header("Expires: 0");
		
		$fp = fopen("php://output", "w");
		$this->fCSV($fp, $s_delimiter, $s_enclosure, $s_escape_char);
		fclose($fp);
		exit;
	}
	
	public function CSV(string $s_delimiter = ',', string $s_enclosure = '"', string $s_escape_char = '\\') : string {
		// Write CSV
		$fp = fopen('php://temp', 'r+');
		$this->fCSV($fp, $s_delimiter, $s_enclosure, $s_escape_char);
		
		// Read CSV
		rewind($fp);
		$s_csv = stream_get_contents($fp);
		
		// Return CSV
		fclose($fp);
		return $s_csv;
	}
	
	public function fCSV($fp, string $s_delimiter = ',', string $s_enclosure = '"', string $s_escape_char = '\\') {
		
		$b_first = true;
		
		foreach($this->Form->getSubmissionStorage()->generator() as $a_row) {
			if($b_first) {
				fputcsv($fp, array_keys($a_row));
				$b_first = false;
			}
			
			fputcsv($fp, array_values($a_row));
		}
		
	}
	
}
