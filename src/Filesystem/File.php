<?php

namespace Reef\Filesystem;

use \Reef\Reef;
use \Reef\Exception\FilesystemException;

class File {
	
	private $Filesystem;
	private $s_filepath;
	
	public function __construct(Filesystem $Filesystem, string $s_filepath) {
		$this->Filesystem = $Filesystem;
		$this->s_filepath = $s_filepath;
	}
	
	public function _setPath(string $s_path) {
		$this->s_filepath = $s_path;
	}
	
	public function getPath() : string {
		return $this->s_filepath;
	}
	
	public function getDir() : string {
		$i_slashPos = strrpos($this->s_filepath, '/');
		
		if($i_slashPos === false) {
			return '';
		}
		
		return substr($this->s_filepath, 0, $i_slashPos);
	}
	
	public function getFileUUIDName() : string {
		$i_slashPos = strrpos($this->s_filepath, '/');
		
		if($i_slashPos === false) {
			return $this->s_filepath;
		}
		
		return substr($this->s_filepath, $i_slashPos+1);
	}
	
	public function getUUID() : string {
		return substr($this->getFileUUIDName(), 0, 32);
	}
	
	public function getFilename() : string {
		return substr($this->getFileUUIDName(), 33);
	}
	
	public function getSize() : int {
		return filesize($this->getPath());
	}
	
	public function stream() {
		header("Content-type: ".$this->Filesystem->getMimeType($this->getPath()));
		header("Content-Disposition: attachment; filename=".$this->getFilename());
		header("Pragma: no-cache");
		header("Expires: 0");
		
		readfile($this->getPath());
		exit;
	}
	
}
