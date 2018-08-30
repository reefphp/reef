<?php

namespace Reef\Filesystem;

use \Reef\Reef;
use \Reef\Exception\FilesystemException;

class Filesystem {
	
	private $Reef;
	private $s_dir;
	private $a_allowedFileTypes = [
		'doc'   => 'application/msword',
		'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'odt'   => 'application/vnd.oasis.opendocument.text',
		
		'xls'   => 'application/vnd.ms-excel',
		'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
		
		'ppt'   => 'application/vnd.ms-powerpoint',
		'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'odp'   => 'application/vnd.oasis.opendocument.presentation',
		
		'pdf'   => 'application/pdf',
		
		'jpeg'  => ['image/jpg', 'image/jpeg'],
		'jpg'   => ['image/jpg', 'image/jpeg'],
		'png'   => 'image/png',
		'gif'   => 'image/gif',
		'txt'   => 'text/plain',
	];
	
	private $a_files;
	
	private $a_mutations = [];
	private $b_inTransaction = false;

	
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
		$this->s_dir = $this->Reef->getOption('files_dir');
		
		if(!is_dir($this->s_dir) || !is_writable($this->s_dir)) {
			throw new \Reef\Exception\InvalidArgumentException("Invalid files dir '".$this->s_dir."'");
		}
	}
	
	public function getAllowedExtensions() {
		return array_keys($this->a_allowedFileTypes);
	}
	
	protected function getDir(string $s_dir) : string {
		$s_dir = trim($s_dir, '/');
		$a_dir = explode('/', $s_dir);
		for($i=1; $i>=0; $i--) {
			if(!isset($a_dir[$i]) || strlen($a_dir[$i]) < 32) {
				continue;
			}
			
			array_splice($a_dir, $i, 0, substr($a_dir[$i], 0, 2));
		}
		$s_dir = implode('/', $a_dir);
		
		$s_dir = $this->s_dir . '/' . $s_dir . '/';
		
		if(!is_dir($s_dir)) {
			mkdir($s_dir, 0777, true);
		}
		
		return $s_dir;
	}
	
	public function inTransaction() {
		return $this->b_inTransaction;
	}
	
	public function startTransaction() {
		$this->b_inTransaction = true;
		$this->a_mutations = [];
	}
	
	public function commitTransaction() {
		foreach($this->a_mutations as $a_mutation) {
			if($a_mutation[0] == 'add') {
				continue;
			}
			
			if($a_mutation[0] == 'move') {
				continue;
			}
			
			if($a_mutation[0] == 'delete') {
				unlink($a_mutation[1]->getPath());
			}
		}
		
		$this->b_inTransaction = false;
		$this->a_mutations = [];
	}
	
	public function rollbackTransaction() {
		foreach($this->a_mutations as $a_mutation) {
			if($a_mutation[0] == 'add') {
				unlink($a_mutation[1]->getPath());
			}
			
			if($a_mutation[0] == 'move') {
				$File = $a_mutation[1][0];
				$s_pathFrom = $a_mutation[1][1];
				
				rename($File->getPath(), $s_pathFrom);
				$File->_setPath($s_pathFrom);
			}
			
			if($a_mutation[0] == 'delete') {
				$a_mutation[1]->_setDelete(false);
				continue;
			}
		}
		
		$this->b_inTransaction = false;
		$this->a_mutations = [];
	}
	
	private function logMutation(string $s_action, $m_data) {
		$this->a_mutations[] = [$s_action, $m_data];
		
		if(!$this->b_inTransaction) {
			$this->commitTransaction();
		}
	}
	
	/**
	 * Returns the mimetype of the given file
	 * 
	 * @param string $s_filename The file name
	 * @return string The mimetype
	 */
	public function getMimeType(string $s_filename) : string {
		if(function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$s_mimetype = finfo_file($finfo, $s_filename);
			finfo_close($finfo);
		}
		else if(function_exists('mime_content_type')) {
			$s_mimetype = mime_content_type($s_filename);
		}
		else {
			throw new FilesystemException('No mimetype function available');
		}
		
		if(($i_pos = strpos($s_mimetype, ';')) !== false) {
			$s_mimetype = substr($s_mimetype, 0, $i_pos);
		}
		
		return $s_mimetype;
	}
	
	/**
	 * Returns the extension of the given file
	 * 
	 * @param string $s_filename The file name
	 * @return string The extension
	 */
	public function getExtension(string $s_filename) : string {
		if(false !== ($i_slashPos = strrpos($s_filename, '/'))) {
			$s_filename = substr($s_filename, $i_slashPos+1);
		}
		
		$i_extOffset = strlen($s_filename);
		do {
			$i_extOffset = strrpos($s_filename, '.', -(strlen($s_filename) - $i_extOffset));
			if($i_extOffset === false) {
				throw new FilesystemException($this->Reef->trans('upload_error_type'));
			}
			$s_extension = strtolower(substr($s_filename, $i_extOffset+1));
		}
		while(!array_key_exists($s_extension, $this->a_allowedFileTypes));
		
		return $s_extension;
	}
	
	public function getMaxUploadSize() {
		return min(array_filter([
			\Reef\parseBytes($this->Reef->getOption('max_upload_size') ?? 0, $this->Reef->getOption('byte_base')),
			\Reef\parseBytes(ini_get('upload_max_filesize'), 1024),
			\Reef\parseBytes(ini_get('post_max_size'), 1024),
			\Reef\parseBytes(ini_get('memory_limit'), 1024),
		]));
	}
	
	public function uploadFiles($s_name) {
		
		if(!array_key_exists($s_name, $_FILES)) {
			throw new FilesystemException('No file found');
		}
		
		$a_files = [];
		if(is_array($_FILES[$s_name]['name'])) {
			foreach($_FILES[$s_name]['name'] as $i => $s_name) {
				if(!is_string($s_name)) {
					throw new FilesystemException('Reef does not support multiple dimensional uploads');
				}
				
				$a_files[] = $this->_uploadFile(array_column($_FILES[$s_name], $i));
			}
		}
		else {
			$a_files[] = $this->_uploadFile($_FILES[$s_name]);
		}
		
		return $a_files;
	}
	
	protected function getContextDir($context) {
		if($context == 'upload') {
			return $this->getDir('_upload');
		}
		
		if($context instanceof \Reef\Components\Component) {
			return $this->getDir('_tmp');
		}
		
		if($context instanceof \Reef\Components\Field) {
			$context = $context->getForm();
		}
		
		if($context instanceof \Reef\Components\FieldValue) {
			$context = $context->getSubmission();
		}
		
		if($context instanceof \Reef\StoredSubmission) {
			return $this->getDir($context->getForm()->getUUID().'/'.$context->getUUID());
		}
		
		if($context instanceof \Reef\TempSubmission) {
			return $this->getDir('_tmp/'.$context->getUUID());
		}
		
		if($context instanceof \Reef\Form\AbstractStoredForm) {
			return $this->getDir($context->getUUID());
		}
		
		if($context instanceof \Reef\Form\TempForm) {
			return $this->getDir('_tmp');
		}
		
		throw new FilesystemException('Invalid context');
	}
	
	public function getFile($s_uuid, $context) {
		$s_contextDir = $this->getContextDir($context);
		
		if(strlen($s_uuid) !== 32) {
			throw new FilesystemException('Invalid file UUID "'.$s_uuid.'"');
		}
		
		if(isset($this->a_files[$s_uuid]) && trim($this->a_files[$s_uuid]->getDir(), '/') == trim($s_contextDir, '/')) {
			return $this->a_files[$s_uuid];
		}
		
		$a_file = glob($s_contextDir . $s_uuid . '*');
		
		if(empty($a_file)) {
			throw new FilesystemException('Could not find file "'.$s_uuid.'"');
		}
		
		$this->a_files[$s_uuid] = new File($this, $a_file[0]);
		return $this->a_files[$s_uuid];
	}
	
	private function _uploadFile($a_upload) {
		$s_dir = $this->getDir('_upload');
		
		if($a_upload['name'] == '') {
			throw new FilesystemException('Error: received invalid empty filename');
		}
		
		switch($a_upload['error']) {
			case UPLOAD_ERR_OK:
				break;
			
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				throw new FilesystemException($this->Reef->trans('upload_error_size'));
			
			case UPLOAD_ERR_PARTIAL:
			case UPLOAD_ERR_NO_FILE:
				throw new FilesystemException('Something went wrong while transferring the file');
			
			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
			case UPLOAD_ERR_EXTENSION:
				throw new FilesystemException('A server error occurred while processing the upload');
			
			default:
				throw new FilesystemException('An unknown error occurred while uploading the file');
		}
		
		$s_mimetype = $this->getMimeType($a_upload['tmp_name']);
		$s_extension = $this->getExtension($a_upload['name']);
		
		if(is_array($this->a_allowedFileTypes[$s_extension])) {
			if(!in_array($s_mimetype, $this->a_allowedFileTypes[$s_extension])) {
				throw new FilesystemException($this->Reef->trans('upload_error_type'));
			}
		}
		else {
			if($s_mimetype != $this->a_allowedFileTypes[$s_extension]) {
				throw new FilesystemException($this->Reef->trans('upload_error_type'));
			}
		}
		
		if($a_upload['size'] == 0 || $a_upload['size'] > $this->getMaxUploadSize()) {
			throw new FilesystemException($this->Reef->trans('upload_error_size'));
		}
		
		$s_cleanName = substr(preg_replace('[^a-zA-Z0-9-_\.]', '', $a_upload['name']), -255);
		
		do {
			$s_destPath = $s_dir.\Reef\unique_id().'_'.$s_cleanName;
		} while(file_exists($s_destPath));
		
		if(!move_uploaded_file($a_upload['tmp_name'], $s_destPath)) {
			throw new FilesystemException('An error occurred while processing the uploaded file');
		}
		
		$File = new File($this, $s_destPath);
		
		$this->logMutation('add', $File);
		
		return $File;
	}
	
	public function changeFileContext(File $File, $context) {
		$s_contextDir = $this->getContextDir($context);
		
		$s_pathFrom = $File->getPath();
		$s_pathTo = $s_contextDir.$File->getFileUUIDName();
		
		if(!rename($s_pathFrom, $s_pathTo)) {
			throw new FilesystemException('Could not move file');
		}
		$File->_setPath($s_pathTo);
		
		$this->logMutation('move', [$File, $s_pathFrom]);
	}
	
	public function deleteFile(File $File) {
		$this->logMutation('delete', $File);
		$File->_setDelete(true);
	}
	
}
