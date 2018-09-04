<?php

namespace Reef\Filesystem;

use \Reef\Reef;
use \Reef\Exception\FilesystemException;

/**
 * The Filesystem class provides logic for large file storage on the harddisk.
 * When necessary, operations can be grouped in a transaction to make them atomic.
 */
class Filesystem {
	
	/**
	 * The Reef object this filesystem belongs to
	 * @type Reef
	 */
	private $Reef;
	
	/**
	 * The root storage directory
	 * @type string
	 */
	private $s_dir;
	
	/**
	 * Array of allowed file types. Each entry is a mime type or list of mime types, indexed by the file extension
	 * @type array
	 */
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
	
	/**
	 * File objects cache
	 * @type File[]
	 */
	private $a_files;
	
	/**
	 * Mutations log used in transactions
	 * @type array
	 */
	private $a_mutations = [];
	
	/**
	 * Switch keeping track whether we are in a transaction or not
	 * @type bool
	 */
	private $b_inTransaction = false;
	
	/**
	 * Constructor
	 * @param Reef $Reef The reef object this filesystem belongs to
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
		$this->s_dir = $this->Reef->getOption('files_dir');
		if($this->s_dir !== null) {
			$this->s_dir = rtrim($this->s_dir, '/') . '/';
		}
	}
	
	/**
	 * Get a list of allowed extensions
	 * @return string[] The extensions
	 */
	public function getAllowedExtensions() {
		return array_keys($this->a_allowedFileTypes);
	}
	
	/**
	 * Get a list of allowed extensions
	 * @return string[] The extensions
	 */
	protected function getDir(string $s_dir) : string {
		if($this->s_dir === null) {
			throw new \Reef\Exception\InvalidArgumentException("No files dir set");
		}
		
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
	
	/**
	 * Return whether we are in a transaction or not
	 * @return bool
	 */
	public function inTransaction() {
		return $this->b_inTransaction;
	}
	
	/**
	 * Start a transaction
	 * Modifications done after startTransaction() can be undone using rollbackTransaction()
	 */
	public function startTransaction() {
		$this->b_inTransaction = true;
		$this->a_mutations = [];
	}
	
	/**
	 * Commit the current transaction
	 * This empties the mutation log, and performs any cleanup actions
	 */
	public function commitTransaction() {
		foreach($this->a_mutations as $a_mutation) {
			if($a_mutation[0] == 'add') {
				continue;
			}
			
			if($a_mutation[0] == 'move' || $a_mutation[0] == 'delete') {
				$this->clearEmptyDirs($a_mutation[1][1]);
			
				if($a_mutation[0] == 'delete') {
					unlink($a_mutation[1][0]->getPath());
					$this->clearEmptyDirs($a_mutation[1][0]->getPath());
				}
				
				continue;
			}
		}
		
		$this->b_inTransaction = false;
		$this->a_mutations = [];
	}
	
	/**
	 * Rollback the current transaction
	 * This reverses all actions in the mutation log, done after startTransaction()
	 */
	public function rollbackTransaction() {
		foreach($this->a_mutations as $a_mutation) {
			if($a_mutation[0] == 'add') {
				unlink($a_mutation[1]->getPath());
				$this->clearEmptyDirs($a_mutation[1]->getPath());
				$a_mutation[1]->_setDelete(true);
				continue;
			}
			
			if($a_mutation[0] == 'move' || $a_mutation[0] == 'delete') {
				$File = $a_mutation[1][0];
				$s_pathFrom = $a_mutation[1][1];
				$s_pathTo = $File->getPath();
				
				rename($s_pathTo, $s_pathFrom);
				$File->_setPath($s_pathFrom);
				$this->clearEmptyDirs($s_pathTo);
				
				if($a_mutation[0] == 'delete') {
					$File->_setDelete(false);
				}
				
				continue;
			}
		}
		
		$this->b_inTransaction = false;
		$this->a_mutations = [];
	}
	
	/**
	 * Add a mutation to the mutation log
	 * @param string $s_action Either 'add', 'move' or 'delete'
	 * @param mixed $m_data Data related to the mutation, to be used to commit/rollback the mutation
	 */
	private function logMutation(string $s_action, $m_data) {
		$this->a_mutations[] = [$s_action, $m_data];
		
		if(!$this->b_inTransaction) {
			$this->commitTransaction();
		}
	}
	
	/**
	 * Remove a directory if it is empty
	 * This function checks whether the given directory is empty, in which case it will delete the directory.
	 * Any parent directory that becomes empty in this way is also deleted, until a non-empty directory (or
	 * the root storage directory) is reached.
	 * @param string $s_path The path to check. May be a path to a (deleted) file or a (deleted) directory
	 */
	private function clearEmptyDirs($s_path) {
		if(substr($s_path, 0, strlen($this->s_dir)) != $this->s_dir) {
			throw new FilesystemException('Invalid directory');
		}
		
		$s_path = substr($s_path, strlen($this->s_dir));
		$s_path = trim($s_path, '/');
		
		if(!is_dir($this->s_dir . $s_path)) {
			$s_path = substr($s_path, 0, strrpos($s_path, '/'));
		}
		
		while(!empty($s_path)) {
			// Check whether the path is a directory; it may
			// already have been deleted in a previous call
			// to clearEmptyDirs()
			if(is_dir($this->s_dir . $s_path)) {
				if($this->numFilesInDir($this->s_dir . $s_path) > 0) {
					break;
				}
				rmdir($this->s_dir . $s_path);
			}
			
			$s_path = substr($s_path, 0, strrpos($s_path, '/'));
		}
	}
	
	/**
	 * Returns the number of files in the given directory
	 * 
	 * @param string $s_dir The directory name
	 * @return int The number of files
	 */
	private function numFilesInDir(string $s_dir) : int {
		return is_dir($s_dir) ? iterator_count(new \FilesystemIterator($s_dir)) : 0;
	}
	
	/**
	 * Returns the number of files the given context has
	 * 
	 * @param mixed $context The context
	 * @return int The number of files
	 */
	public function numFilesInContext($context) : int {
		return $this->numFilesInDir($this->getContextDir($context));
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
			$i_extOffset = strrpos(substr($s_filename, 0, $i_extOffset), '.');
			if($i_extOffset === false) {
				throw new FilesystemException($this->Reef->trans('upload_error_type'));
			}
			$s_extension = strtolower(substr($s_filename, $i_extOffset+1));
		}
		while(!array_key_exists($s_extension, $this->a_allowedFileTypes));
		
		return $s_extension;
	}
	
	/**
	 * Return the maximum upload size
	 * The maximum upload size is the minimum of:
	 *  - upload_max_filesize in php.ini
	 *  - post_max_size in php.ini
	 *  - memory_limit in php.ini
	 *  - max_upload_size in Reef options
	 * Hence, note that the max_upload_size option is not necessarily the effective maximum
	 * 
	 * @return int Maximum number of bytes in an uploaded file
	 */
	public function getMaxUploadSize() : int {
		return min(array_filter([
			\Reef\parseBytes($this->Reef->getOption('max_upload_size') ?? 0, $this->Reef->getOption('byte_base')),
			\Reef\parseBytes(ini_get('upload_max_filesize'), 1024),
			\Reef\parseBytes(ini_get('post_max_size'), 1024),
			\Reef\parseBytes(ini_get('memory_limit'), 1024),
		]));
	}
	
	/**
	 * Upload files
	 * This function handles uploaded files with the given value key. Only single file
	 * uploads and a list of uploads are supported: multi-dimensional uploads are not.
	 * @param string $s_key The key in $_FILES to process
	 * @return File[] The uploaded files
	 */
	public function uploadFiles($s_key) {
		
		if(!array_key_exists($s_key, $_FILES)) {
			throw new FilesystemException('No file found');
		}
		
		$a_files = [];
		if(is_array($_FILES[$s_key]['name'])) {
			foreach($_FILES[$s_key]['name'] as $i => $s_name) {
				if(!is_string($s_name)) {
					throw new FilesystemException('Reef does not support multiple dimensional uploads');
				}
				
				$a_file = array_combine(array_keys($_FILES[$s_key]), array_column($_FILES[$s_key], $i));
				
				$a_files[] = $this->_uploadFile($a_file);
			}
		}
		else {
			$a_files[] = $this->_uploadFile($_FILES[$s_key]);
		}
		
		return $a_files;
	}
	
	/**
	 * Retrieve the directory belonging to a context
	 * The context can be a component, field, value, form or submission, or some internal identification string
	 * @param mixed $context The context
	 * @return string The directory belonging to the context
	 */
	protected function getContextDir($context) {
		if($context == 'upload') {
			return $this->getDir('_upload');
		}
		
		if($context == 'trash') {
			return $this->getDir('_trash');
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
	
	/**
	 * Retrieve a file
	 * @param string $s_uuid The uuid of the file to obtain
	 * @param mixed $context @see getContextDir()
	 * @return File The file object
	 * @throws FilesystemException If the file is not found
	 */
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
	
	/**
	 * Perform a single file upload
	 * @param array $a_upload The upload data from $_FILES, belonging to a single file
	 * @return File The file object
	 * @throws FilesystemException If the upload did not succeed
	 */
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
		$this->a_files[$File->getUUID()] = $File;
		
		return $File;
	}
	
	/**
	 * Move the file between contexts
	 * Should be used to e.g. move a file from the temporary upload directory to its final destination
	 * @param File $File The file to move
	 * @param mixed $context @see getContextDir()
	 * @throws FilesystemException If the move did not succeed
	 */
	public function changeFileContext(File $File, $context) {
		$s_contextDir = $this->getContextDir($context);
		
		$s_pathFrom = $File->getPath();
		$s_pathTo = $s_contextDir.$File->getFileUUIDName();
		
		if(!rename($s_pathFrom, $s_pathTo)) {
			throw new FilesystemException('Could not move file');
		}
		$File->_setPath($s_pathTo);
		
		if($context != 'trash') {
			$this->logMutation('move', [$File, $s_pathFrom]);
		}
		else {
			$this->logMutation('delete', [$File, $s_pathFrom]);
			$File->_setDelete(true);
		}
	}
	
	/**
	 * Delete a file
	 * Should be used to delete a file
	 * @param File $File The file to delete
	 */
	public function deleteFile(File $File) {
		$this->changeFileContext($File, 'trash');
	}
	
	/**
	 * Remove an entire context directory, including contents
	 * @param mixed $context @see getContextDir()
	 * @throws FilesystemException If the context does not belong to a stored submission or stored form
	 */
	public function removeContextDir($context) {
		if($this->s_dir === null) {
			return;
		}
		
		if(!($context instanceof \Reef\StoredSubmission) && !($context instanceof \Reef\Form\AbstractStoredForm)) {
			throw new FilesystemException('Invalid remove context');
		}
		
		$s_contextDir = $this->getContextDir($context);
		
		\Reef\rmTree($s_contextDir, true);
		$this->clearEmptyDirs($s_contextDir);
	}
	
}
