<?php

namespace Reef\Storage;

use Reef\Exception\IOException;

class JSONStorage implements Storage {
	private $s_path;
	
	const INC_FILE = '.json_storage_inc';
	
	public function __construct(string $s_path) {
		$s_path = rtrim($s_path, '/') . '/';
		
		if(!is_dir($s_path) || !is_writable($s_path)) {
			throw new IOException('Inexistent path "'.$s_path.'".');
		}
		
		$s_incFilePath = $s_path . self::INC_FILE;
		if(!file_exists($s_incFilePath)) {
			if(!file_put_contents($s_incFilePath, '1')) {
				throw new IOException('Could not write to "'.$s_incFilePath.'".');
			}
		}
		
		$this->s_path = $s_path;
	}
	
	/**
	 * @inherit
	 */
	public function deleteStorage() {
		return \Reef\rmTree($this->s_path);
	}
	
	private function getIncFile() : string {
		return $this->s_path . self::INC_FILE;
	}
	
	/**
	 * @inherit
	 */
	public function list() : array {
		$a_files = glob($this->s_path.'*.json');
		$a_ids = [];
		foreach($a_files as $i => $s_file) {
			$s_file = substr($s_file, strlen($this->s_path));
			
			if(preg_match('%^(\d+)\.json$%', $s_file, $a_matches)) {
				$a_ids[] = $a_matches[1];
			}
		}
		natsort($a_ids);
		return $a_ids;
	}
	
	/**
	 * @inherit
	 */
	public function insert(array $a_data) : int {
		return $this->insertAs(null, $a_data);
	}
	
	/**
	 * @inherit
	 */
	public function insertAs(?int $i_entryId, array $a_data) : int {
		$s_incFilePath = $this->getIncFile();
		
		$fp = @fopen($s_incFilePath, 'r+');
		
		if(!$fp) {
			throw new IOException('Could not open "'.$s_incFilePath.'".');
		}
		
		try {
			if(!flock($fp, LOCK_EX)) {
				throw new IOException('Could not acquire lock on "'.$s_incFilePath.'".');
			}
			
			if(!fscanf($fp, '%D', $i_nextEntryId)) {
				throw new IOException('Could not read "'.$s_incFilePath.'".');
			}
			
			if($i_entryId === null) {
				$i_entryId = $i_nextEntryId;
			}
			
			$s_entryFilePath = $this->s_path . $i_entryId.'.json';
			if(file_exists($s_entryFilePath)) {
				throw new IOException('Entry "'.$i_entryId.'" already exists.');
			}
			
			if(!file_put_contents($s_entryFilePath, json_encode($a_data))) {
				throw new IOException('Could not write to "'.$s_entryFilePath.'".');
			}
			
			if((int)$i_nextEntryId <= $i_entryId) {
				
				if(!ftruncate($fp, 0) || !rewind($fp)) {
					throw new IOException('Could not truncate "'.$s_incFilePath.'".');
				}
				
				if(!fprintf($fp, "%d", $i_entryId+1)) {
					throw new IOException('Could not write to "'.$s_incFilePath.'".');
				}
				
				if(!fflush($fp) || !flock($fp, LOCK_UN)) {
					throw new IOException('Could not flush or release lock on "'.$s_incFilePath.'".');
				}
			}
		}
		catch(IOException $e) {
			fclose($fp);
			throw $e;
		}
		
		if(!fclose($fp)) {
			throw new IOException('Could not close "'.$s_incFilePath.'".');
		}
		
		return $i_entryId;
	}
	
	/**
	 * @inherit
	 */
	public function update(int $i_entryId, array $a_data) {
		
		$s_entryFilePath = $this->s_path . $i_entryId.'.json';
		
		if(!file_exists($s_entryFilePath)) {
			throw new IOException('Entry "'.$i_entryId.'" does not exist.');
		}
		
		if(!file_put_contents($s_entryFilePath, json_encode($a_data))) {
			throw new IOException('Could not write to "'.$s_entryFilePath.'".');
		}
		
	}
	
	/**
	 * @inherit
	 */
	public function delete(int $i_entryId) {
		$s_entryFilePath = $this->s_path . $i_entryId.'.json';
		
		if(!file_exists($s_entryFilePath)) {
			throw new IOException('Entry "'.$i_entryId.'" does not exist.');
		}
		
		if(!unlink($s_entryFilePath)) {
			throw new IOException('Could not remove "'.$s_entryFilePath.'".');
		}
		
	}
	
	/**
	 * @inherit
	 */
	public function get(int $i_entryId) : array {
		$s_entryFilePath = $this->s_path . $i_entryId.'.json';
		
		if(!file_exists($s_entryFilePath)) {
			throw new IOException('Entry "'.$i_entryId.'" does not exist.');
		}
		
		$s_content = @file_get_contents($s_entryFilePath);
		if($s_content === false) {
			throw new IOException('Could not read "'.$s_entryFilePath.'".');
		}
		
		return json_decode($s_content, true);
	}
	
	/**
	 * @inherit
	 */
	public function exists(int $i_entryId) : bool {
		$s_entryFilePath = $this->s_path . $i_entryId.'.json';
		
		return file_exists($s_entryFilePath);
	}
	
	/**
	 * @inherit
	 */
	public function next() : int {
		$s_incFilePath = $this->getIncFile();
		
		$fp = @fopen($s_incFilePath, 'r+');
		
		if(!$fp) {
			throw new IOException('Could not open "'.$s_incFilePath.'".');
		}
		
		try {
			if(!flock($fp, LOCK_EX)) {
				throw new IOException('Could not acquire lock on "'.$s_incFilePath.'".');
			}
			
			if(!fscanf($fp, '%D', $i_entryId)) {
				throw new IOException('Could not read "'.$s_incFilePath.'".');
			}
		}
		catch(IOException $e) {
			fclose($fp);
			throw $e;
		}
		
		return $i_entryId;
	}
	
	
}
