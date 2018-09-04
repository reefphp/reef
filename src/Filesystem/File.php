<?php

namespace Reef\Filesystem;

use \Reef\Reef;
use \Reef\Exception\FilesystemException;

/**
 * A single file in the Filesystem
 */
class File {
	
	/**
	 * The Filesystem this file belongs to
	 * @type Filesystem
	 */
	private $Filesystem;
	
	/**
	 * The path to the file
	 * @type string
	 */
	private $s_filepath;
	
	/**
	 * Whether this file is (marked to be) deleted
	 * @type bool
	 */
	private $b_deleted = false;
	
	/**
	 * Constructor
	 * @param Filesystem $Filesystem The Filesystem this file belongs to
	 * @param string $s_filepath The path to the file
	 */
	public function __construct(Filesystem $Filesystem, string $s_filepath) {
		$this->Filesystem = $Filesystem;
		$this->s_filepath = $s_filepath;
	}
	
	/**
	 * (Internal) Set the path of this file
	 * @param string $s_path The new path to the file
	 */
	public function _setPath(string $s_path) {
		$this->s_filepath = $s_path;
	}
	
	/**
	 * (Internal) Set the deleted flag of this file
	 * @param bool $b_delete New delete value
	 */
	public function _setDelete(bool $b_delete) {
		$this->b_deleted = $b_delete;
	}
	
	/**
	 * Return whether this file is (marked to be) deleted
	 * @return bool
	 */
	public function isDeleted() {
		return $this->b_deleted;
	}
	
	/**
	 * Return the path to the file (directory + UUID + filename)
	 * @return string
	 */
	public function getPath() : string {
		return $this->s_filepath;
	}
	
	/**
	 * Return the directory of the file
	 * @return string
	 */
	public function getDir() : string {
		$i_slashPos = strrpos($this->s_filepath, '/');
		
		if($i_slashPos === false) {
			return '';
		}
		
		return substr($this->s_filepath, 0, $i_slashPos);
	}
	
	/**
	 * Return the filename with UUID
	 * @return string
	 */
	public function getFileUUIDName() : string {
		$i_slashPos = strrpos($this->s_filepath, '/');
		
		if($i_slashPos === false) {
			return $this->s_filepath;
		}
		
		return substr($this->s_filepath, $i_slashPos+1);
	}
	
	/**
	 * Return the UUID of the file
	 * @return string
	 */
	public function getUUID() : string {
		return substr($this->getFileUUIDName(), 0, 32);
	}
	
	/**
	 * Return the filename of the file
	 * @return string
	 */
	public function getFilename() : string {
		return substr($this->getFileUUIDName(), 33);
	}
	
	/**
	 * Return the size of the file
	 * @return int
	 */
	public function getSize() : int {
		return filesize($this->getPath());
	}
	
	/**
	 * Return the extension of the file
	 * @return string
	 */
	public function getExtension() : string {
		return $this->Filesystem->getExtension($this->getPath());
	}
	
	/**
	 * Return the mimetype of the file
	 * @return string
	 */
	public function getMimeType() : string {
		return $this->Filesystem->getMimeType($this->getPath());
	}
	
	/**
	 * Stream the contents of this file to the browser
	 * This function exits the script
	 */
	public function stream() {
		header("Content-type: ".$this->Filesystem->getMimeType($this->getPath()));
		header("Content-Disposition: attachment; filename=".$this->getFilename());
		header("Pragma: no-cache");
		header("Expires: 0");
		
		readfile($this->getPath());
		exit;
	}
	
}
