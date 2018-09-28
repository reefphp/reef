<?php

namespace ReefTests\integration\Components\Upload;

require_once(__DIR__ . '/../../../filesystem_move_uploaded_file.php');

trait CommonUploadTrait {
	
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		if(!is_dir(static::FILES_DIR)) {
			mkdir(static::FILES_DIR, 0777);
		}
	}
	
	protected function createComponent() {
		return new \Reef\Components\Upload\UploadComponent;
	}
	
	public function getReefOptions() {
		$a_options = parent::getReefOptions();
		$a_options['files_dir'] = static::FILES_DIR;
		return $a_options;
	}
	
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		\Reef\rmTree(static::FILES_DIR, true);
	}
	
	protected function uploadFile(string $s_filename, string $s_content, ?string &$s_uuid = null) {
		
		$s_filepath = static::FILES_DIR . '/' . \Reef\unique_id();
		
		file_put_contents($s_filepath, $s_content);
		
		$_FILES = [
			'files' => [
				'name' => $s_filename,
				'type' => 'text/plain',
				'size' => strlen($s_content),
				'tmp_name' => $s_filepath,
				'error' => UPLOAD_ERR_OK,
			],
		];
		
		ob_start();
		static::$Reef->internalRequest('component:reef:upload:upload');
		$s_output = ob_get_clean();
		
		$_FILES = [];
		
		$a_output = json_decode($s_output, true);
		
		if($a_output['success']) {
			$s_uuid = $a_output['files'][0];
		}
		
		if(func_num_args() >= 3) {
			if(empty($s_uuid)) {
				throw new \Reef\Exception\RuntimeException('Test upload failed');
			}
		}
		
		return $a_output;
	}
	
	protected function copyFile(string $s_filename, string $s_content, ?string &$s_uuid = null) {
		
		$s_filepath = $this->newTmpFile($s_filename, $s_content);
		
		$s_uuid = static::$Reef->getSetup()->getComponent('reef:upload')->copyFile($s_filepath);
		
		return $s_uuid;
	}
	
	protected function newTmpFile(string $s_filename, string $s_content) {
		
		$s_filepath = static::FILES_DIR . '/' . \Reef\unique_id().'_'.$s_filename;
		
		file_put_contents($s_filepath, $s_content);
		
		return $s_filepath;
	}
}
