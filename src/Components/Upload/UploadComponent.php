<?php

namespace Reef\Components\Upload;

use Reef\Components\Component;
use \Reef\Components\Traits\Required\RequiredComponentInterface;
use \Reef\Components\Traits\Required\RequiredComponentTrait;

class UploadComponent extends Component implements RequiredComponentInterface {
	
	use RequiredComponentTrait;
	
	const COMPONENT_NAME = 'reef:upload';
	const PARENT_NAME = null;
	
	private $a_defaultTypes = ['doc', 'docx', 'odt', 'pdf', 'jpg', 'jpeg', 'png'];
	
	/**
	 * Get the default list of accepted upload file extensions
	 * @return string[] The extensions
	 */
	public function getDefaultTypes() {
		return $this->a_defaultTypes;
	}
	
	/**
	 * Set the default list of accepted upload file extensions
	 * @param string[] $a_extensions The extensions
	 */
	public function setDefaultTypes(array $a_extensions) {
		if($this->Reef !== null) {
			throw new \Reef\Exception\BadMethodCallException("Can only set default types during initialization");
		}
		$this->a_defaultTypes = array_map('strtolower', $a_extensions);
	}
	
	/**
	 * Add accepted upload file extensions
	 * @param string[] $a_extensions The extensions to add
	 */
	public function addDefaultTypes(array $a_extensions) {
		if($this->Reef !== null) {
			throw new \Reef\Exception\BadMethodCallException("Can only add default types during initialization");
		}
		$this->a_defaultTypes = array_unique(array_merge($this->a_defaultTypes, array_map('strtolower', $a_extensions)));
	}
	
	/**
	 * Remove accepted upload file extensions
	 * @param string[] $a_extensions The extensions to remove
	 */
	public function removeDefaultTypes(array $a_extensions) {
		if($this->Reef !== null) {
			throw new \Reef\Exception\BadMethodCallException("Can only remove default types during initialization");
		}
		$this->a_defaultTypes = array_diff($this->a_defaultTypes, array_map('strtolower', $a_extensions));
	}
	
	/**
	 * @inherit
	 */
	public function checkSetup() {
		if($this->Reef->getOption('files_dir') === null) {
			throw new \Reef\Exception\InvalidArgumentException("No files dir set");
		}
		
		if($this->Reef->getSetup()->getSessionObject() instanceof \Reef\Session\NoSession) {
			throw new \Reef\Exception\InvalidArgumentException("Upload component requires session");
		}
		
		$a_invalidExtensions = array_diff($this->a_defaultTypes, $this->getReef()->getDataStore()->getFilesystem()->getAllowedExtensions());
		if(!empty($a_invalidExtensions)) {
			throw new \Reef\Exception\InvalidArgumentException("Invalid upload default types ".implode(',', $a_invalidExtensions)." ");
		}
	}
	
	/**
	 * @inherit
	 */
	public static function getDir() : string {
		return __DIR__.'/';
	}
	
	/**
	 * @inherit
	 */
	public function validateDeclaration(array $a_declaration, array &$a_errors = null) : bool {
		$b_valid = true;
		
		if(isset($a_declaration['max_files'])) {
			if($a_declaration['max_files'] <= 0 || $a_declaration['max_files'] > min(ini_get('max_file_uploads'), UploadField::MAX_FILES)) {
				$a_errors['max_files'] = 'Invalid number of max files';
				$b_valid = false;
			}
		}
		
		return $b_valid;
	}
	
	/**
	 * @inherit
	 */
	public function getConfiguration() : array {
		if($this->a_configuration !== null) {
			return $this->a_configuration;
		}
		
		$this->a_configuration = parent::getConfiguration();
		$this->a_configuration['basicDefinition']['fields'][1]['max'] = ini_get('max_file_uploads');
		
		$a_types = $this->getReef()->getDataStore()->getFilesystem()->getAllowedExtensions();
		sort($a_types);
		$this->a_configuration['advancedDefinition']['fields'][0]['options'] = array_map(function($s_type) {
			return [
				'name' => $s_type,
				'locale' => ['en_US' => '.'.$s_type],
				'default' => in_array($s_type, $this->a_defaultTypes),
			];
		}, $a_types);
		
		return $this->a_configuration;
	}
	
	/**
	 * @inherit
	 */
	public function supportedStorages() : ?array {
		return [
			'pdo_mysql',
			'pdo_sqlite',
		];
	}
	
	/**
	 * @inherit
	 */
	public function internalRequest(string $s_requestHash, array $a_options = []) {
		if($s_requestHash != 'upload') {
			throw new \Reef\Exception\InvalidArgumentException('Invalid request command');
		}
		
		$a_return = [];
		
		try {
			$Filesystem = $this->getReef()->getDataStore()->getFilesystem();
			$a_files = $Filesystem->uploadFiles('files');
			
			$a_fileUUIDs = [];
			foreach($a_files as $File) {
				$a_fileUUIDs[] = $File->getUUID();
			}
			
			$Session = $this->getReef()->getSession();
			$Session->set($this, 'uploaded_files', array_merge(
				$Session->get($this, 'uploaded_files', []),
				$a_fileUUIDs
			));
			
			$a_return['success'] = true;
			$a_return['files'] = $a_fileUUIDs;
		}
		catch(\Exception $e) {
			$a_return['success'] = false;
			$a_return['error'] = $e->getMessage();
		}
		
		echo(json_encode($a_return));
		\Reef\stop();
	}
	
	/**
	 * 'Upload' a file by copying it from disk
	 * @param string $s_fileName The file path + name
	 * @return string The file UUID
	 */
	public function copyFile(string $s_fileName) {
		$Filesystem = $this->getReef()->getDataStore()->getFilesystem();
		$File = $Filesystem->addFileByCopy($s_fileName);
		
		$Session = $this->getReef()->getSession();
		$Session->set($this, 'uploaded_files', array_merge(
			$Session->get($this, 'uploaded_files', []),
			[$File->getUUID()]
		));
		
		return $File->getUUID();
	}
	
}
