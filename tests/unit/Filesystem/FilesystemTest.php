<?php

namespace ReefTests\unit\Filesystem;

require_once(__DIR__ . '/../../filesystem_move_uploaded_file.php');

use PHPUnit\Framework\TestCase;
use \Reef\Exception\FilesystemException;
use \Reef\Exception\BadMethodCallException;

final class FilesystemTest extends TestCase {
	
	const FILES_DIR = __DIR__ . '/../../../var/tmp/test/filesystemtest';
	
	private static $Reef;
	private static $Form;
	private static $Submission;
	private static $Filesystem;
	
	public static function setUpBeforeClass() {
		
		if(!is_dir(static::FILES_DIR)) {
			mkdir(static::FILES_DIR, 0777, true);
		}
		
		$PDO = new \PDO("sqlite:".static::FILES_DIR."/test.db");
		$PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		
		// Specify which components we want to use
		$Setup = new \Reef\ReefSetup(
			\Reef\Storage\PDOStorageFactory::createFactory($PDO),
			new \Reef\Layout\bootstrap4\bootstrap4(),
			new \Reef\Session\TmpSession()
		);
		
		static::$Reef = new \Reef\Reef(
			$Setup,
			[
				'files_dir' => static::FILES_DIR,
			]
		);
		
		static::$Filesystem = static::$Reef->getDataStore()->getFilesystem();
	}
	
	public function testAllowedExtensionsIsArray() {
		$this->assertInternalType('array', static::$Filesystem->getAllowedExtensions());
	}
	
	public function testIsPermittedPathOnValidPath() {
		$this->assertTrue(\Reef\Filesystem\Filesystem::isPermittedPath(__FILE__));
	}
	
	public function testIsPermittedPathOnInvalidPath() {
		$this->assertFalse(\Reef\Filesystem\Filesystem::isPermittedPath('phar://'.__FILE__));
	}
	
	public function testAllowedExtensionSetters() {
		$Setup = new \Reef\ReefSetup(
			new \Reef\Storage\NoStorageFactory(),
			new \Reef\Layout\bootstrap4\bootstrap4(),
			new \Reef\Session\TmpSession()
		);
		
		// Before init
		$Filesystem = $Setup->getFilesystem();
		
		$Filesystem->setAllowedTypes(['txt' => 'text/plain']);
		$this->assertSame(['txt'], $Filesystem->getAllowedExtensions());
		
		$Filesystem->addAllowedTypes(['png' => 'image/png']);
		$this->assertSame(['txt', 'png'], $Filesystem->getAllowedExtensions());
		
		$Filesystem->removeAllowedTypes(['txt']);
		$this->assertSame(['png'], $Filesystem->getAllowedExtensions());
		
		// Init
		$Reef = new \Reef\Reef(
			$Setup,
			[
				'files_dir' => static::FILES_DIR,
			]
		);
		
		// After init
		$i_nonerrors = 0;
		try {
			$Filesystem->setAllowedTypes(['txt' => 'text/plain']);
			$i_nonerrors++;
		} catch(BadMethodCallException $e) {}
		
		try {
			$Filesystem->addAllowedTypes(['txt' => 'text/plain']);
			$i_nonerrors++;
		} catch(BadMethodCallException $e) {}
		
		try {
			$Filesystem->removeAllowedTypes(['txt']);
			$i_nonerrors++;
		} catch(BadMethodCallException $e) {}
		
		$this->assertSame(0, $i_nonerrors, "Some allowed type setters (".$i_nonerrors.") did not throw an error when run after initialization");
	}
	
	public function testTransactionState() {
		$this->assertFalse(static::$Filesystem->inTransaction());
		
		static::$Filesystem->startTransaction();
		$this->assertTrue(static::$Filesystem->inTransaction());
		
		static::$Filesystem->commitTransaction();
		$this->assertFalse(static::$Filesystem->inTransaction());
		
		static::$Filesystem->startTransaction();
		$this->assertTrue(static::$Filesystem->inTransaction());
		
		static::$Filesystem->rollbackTransaction();
		$this->assertFalse(static::$Filesystem->inTransaction());
	}
	
	public function testGetExtensionReturnsValidExtension() {
		$this->assertSame('txt', static::$Filesystem->getExtension('path/to/file.txt'));
	}
	
	public function testGetExtensionThrowsInvalidExtension() {
		$this->expectException(FilesystemException::class);
		
		static::$Filesystem->getExtension('path/to/file.invalid');
	}
	
	public function testGetExtensionThrowsWithDots() : string {
		$this->expectException(FilesystemException::class);
		
		static::$Filesystem->getExtension('path/to/asdf..asdf.....asdf...asdf');
	}
	
	public function testGetMaxUploadSize() {
		$this->assertInternalType('int', static::$Filesystem->getMaxUploadSize());
	}
	
	public function testRejectsInvalidExtMime() {
		$s_filepath = static::FILES_DIR . '/file.jpeg';
		$s_content = 'content';
		file_put_contents($s_filepath, $s_content);
		
		$_FILES = [
			'identifier' => [
				'name' => 'file.jpeg',
				'type' => 'text/plain',
				'size' => strlen($s_content),
				'tmp_name' => $s_filepath,
				'error' => UPLOAD_ERR_OK,
			],
		];
		
		$this->expectException(FilesystemException::class);
		static::$Filesystem->uploadFiles('identifier');
	}
	
	public function testCopyValidFile() {
		$s_filepath = static::FILES_DIR . '/file.txt';
		$s_content = 'content';
		file_put_contents($s_filepath, $s_content);
		
		$File = static::$Filesystem->addFileByCopy($s_filepath);
		
		$this->assertSame('txt', $File->getExtension());
		
		$this->assertSame($File, static::$Filesystem->getFile($File->getUUID(), 'upload'));
		
		$this->assertSame(1, static::$Filesystem->numFilesInContext('upload'));
		
		static::$Filesystem->deleteFile($File);
		
		$this->assertSame(0, static::$Filesystem->numFilesInContext('upload'));
	}
	
	public function testUploadValidFile() : string {
		$s_filepath = static::FILES_DIR . '/file.txt';
		$s_content = 'content';
		file_put_contents($s_filepath, $s_content);
		
		$_FILES = [
			'identifier' => [
				'name' => 'file.txt',
				'type' => 'text/plain',
				'size' => strlen($s_content),
				'tmp_name' => $s_filepath,
				'error' => UPLOAD_ERR_OK,
			],
		];
		
		$a_files = static::$Filesystem->uploadFiles('identifier');
		
		$this->assertSame(1, count($a_files));
		
		$File = $a_files[0];
		
		$this->assertSame($_FILES['identifier']['name'], $File->getFilename());
		$this->assertSame($_FILES['identifier']['size'], $File->getSize());
		$this->assertSame($_FILES['identifier']['type'], $File->getMimeType());
		$this->assertSame('txt', $File->getExtension());
		
		$this->assertSame($File, static::$Filesystem->getFile($File->getUUID(), 'upload'));
		
		return $File->getUUID();
	}
	
	/**
	 * @depends testUploadValidFile
	 */
	public function testMoveFile(string $s_uuid) : string {
		$File = static::$Filesystem->getFile($s_uuid, 'upload');
		
		$this->assertSame(1, static::$Filesystem->numFilesInContext('upload'));
		
		$Form = static::$Reef->newTempForm();
		static::$Filesystem->changeFileContext($File, $Form);
		
		$this->assertSame(0, static::$Filesystem->numFilesInContext('upload'));
		
		return $s_uuid;
	}
	
	/**
	 * @depends testMoveFile
	 */
	public function testMovedFileNotInUpload(string $s_uuid) {
		$this->expectException(FilesystemException::class);
		static::$Filesystem->getFile($s_uuid, 'upload');
	}
	
	/**
	 * @depends testMoveFile
	 */
	public function testDeleteFile(string $s_uuid) {
		$Form = static::$Reef->newTempForm();
		
		$File = static::$Filesystem->getFile($s_uuid, $Form);
		
		$this->assertFalse($File->isDeleted());
		
		static::$Filesystem->deleteFile($File);
		
		$this->assertTrue($File->isDeleted());
	}
	
	public function testUploadValidFiles() : array {
		$s_filepath1 = static::FILES_DIR . '/file1.txt';
		$s_content1 = 'content1';
		file_put_contents($s_filepath1, $s_content1);
		
		$s_filepath2 = static::FILES_DIR . '/file2.txt';
		$s_content2 = 'content2';
		file_put_contents($s_filepath2, $s_content2);
		
		$_FILES = [
			'upload' => [
				'name' => [
					'file1.txt',
					'file2.txt',
				],
				'type' => [
					'text/plain',
					'text/plain',
				],
				'size' => [
					strlen($s_content1),
					strlen($s_content2),
				],
				'tmp_name' => [
					$s_filepath1,
					$s_filepath2,
				],
				'error' => [
					UPLOAD_ERR_OK,
					UPLOAD_ERR_OK,
				],
			],
		];
		
		$a_files = static::$Filesystem->uploadFiles('upload');
		
		$this->assertSame(2, count($a_files));
		
		$File1 = $a_files[0];
		$File2 = $a_files[1];
		
		$this->assertSame($_FILES['upload']['name'][0], $File1->getFilename());
		$this->assertSame($_FILES['upload']['size'][0], $File1->getSize());
		$this->assertSame($_FILES['upload']['type'][0], $File1->getMimeType());
		
		$this->assertSame($File1, static::$Filesystem->getFile($File1->getUUID(), 'upload'));
		
		$this->assertSame($_FILES['upload']['name'][1], $File2->getFilename());
		$this->assertSame($_FILES['upload']['size'][1], $File2->getSize());
		$this->assertSame($_FILES['upload']['type'][1], $File2->getMimeType());
		
		$this->assertSame($File2, static::$Filesystem->getFile($File2->getUUID(), 'upload'));
		
		return [$File1, $File2];
	}
	
	/**
	 * @depends testUploadValidFiles
	 */
	public function testMoveFiles(array $a_files) : array {
		[$File1, $File2] = $a_files;
		
		$Form1 = static::$Reef->newTempForm();
		$Form2 = static::$Reef->newTempStorableForm();
		
		$this->assertSame(2, static::$Filesystem->numFilesInContext('upload'));
		
		static::$Filesystem->changeFileContext($File1, $Form1);
		
		$this->assertSame(1, static::$Filesystem->numFilesInContext('upload'));
		
		static::$Filesystem->changeFileContext($File2, $Form2);
		
		$this->assertSame(0, static::$Filesystem->numFilesInContext('upload'));
		
		return [$File1, $File2, $Form1, $Form2];
	}
	
	/**
	 * @depends testMoveFiles
	 */
	public function testMoveBetweenTempForms(array $a_objects) : array {
		[$File1, $File2, $Form1, $Form2] = $a_objects;
		
		$Form1B = static::$Reef->newTempForm();
		
		static::$Filesystem->changeFileContext($File1, $Form1B);
		
		$this->assertSame($File1, static::$Filesystem->getFile($File1->getUUID(), $Form1));
		
		return [$Form1B, $File2, $Form1, $Form2];
	}
	
	/**
	 * @depends testMoveBetweenTempForms
	 */
	public function testMoveTransaction(array $a_objects) : array {
		[$File1, $File2, $Form1, $Form2] = $a_objects;
		
		$Form2B = static::$Reef->newTempStorableForm();
		
		$this->assertSame(1, static::$Filesystem->numFilesInContext($Form2));
		$this->assertSame(0, static::$Filesystem->numFilesInContext($Form2B));
		
		static::$Filesystem->startTransaction();
		static::$Filesystem->changeFileContext($File2, $Form2B);
		
		$this->assertSame(0, static::$Filesystem->numFilesInContext($Form2));
		$this->assertSame(1, static::$Filesystem->numFilesInContext($Form2B));
		
		static::$Filesystem->rollbackTransaction();
		
		$this->assertSame(1, static::$Filesystem->numFilesInContext($Form2));
		$this->assertSame(0, static::$Filesystem->numFilesInContext($Form2B));
		
		return $a_objects;
	}
	
	/**
	 * @depends testMoveTransaction
	 */
	public function testDeleteTransaction(array $a_objects) : array {
		[$File1, $File2, $Form1, $Form2] = $a_objects;
		
		$this->assertSame(1, static::$Filesystem->numFilesInContext($Form2));
		$this->assertSame(0, static::$Filesystem->numFilesInContext('trash'));
		$this->assertFalse($File2->isDeleted());
		
		static::$Filesystem->startTransaction();
		static::$Filesystem->deleteFile($File2);
		
		$this->assertSame(0, static::$Filesystem->numFilesInContext($Form2));
		$this->assertSame(1, static::$Filesystem->numFilesInContext('trash'));
		$this->assertTrue($File2->isDeleted());
		
		static::$Filesystem->rollbackTransaction();
		
		$this->assertSame(1, static::$Filesystem->numFilesInContext($Form2));
		$this->assertSame(0, static::$Filesystem->numFilesInContext('trash'));
		$this->assertFalse($File2->isDeleted());
		
		return $a_objects;
	}
	
	/**
	 * @depends testDeleteTransaction
	 */
	public function testAddTransaction() {
		$s_filepath = static::FILES_DIR . '/file_trans.txt';
		$s_content = 'some_content';
		file_put_contents($s_filepath, $s_content);
		
		$_FILES = [
			'upload_name' => [
				'name' => 'myfile.txt',
				'type' => 'text/plain',
				'size' => strlen($s_content),
				'tmp_name' => $s_filepath,
				'error' => UPLOAD_ERR_OK,
			],
		];
		
		$this->assertSame(0, static::$Filesystem->numFilesInContext('upload'));
		
		static::$Filesystem->startTransaction();
		$a_files = static::$Filesystem->uploadFiles('upload_name');
		
		$this->assertSame(1, count($a_files));
		$File = $a_files[0];
		
		$this->assertSame(1, static::$Filesystem->numFilesInContext('upload'));
		$this->assertFalse($File->isDeleted());
		
		static::$Filesystem->rollbackTransaction();
		$this->assertSame(0, static::$Filesystem->numFilesInContext('upload'));
		$this->assertTrue($File->isDeleted());
	}
	
	public static function tearDownAfterClass() {
		\Reef\rmTree(static::FILES_DIR);
		unset($_FILES);
	}
	
}
