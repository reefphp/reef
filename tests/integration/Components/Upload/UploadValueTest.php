<?php

namespace tests\Components;

require_once(__DIR__ . '/../FieldValueTestCase.php');
require_once(__DIR__ . '/CommonUploadTrait.php');

final class UploadValueTest extends FieldValueTestCase {
	
	use CommonUploadTrait;

	const FILES_DIR = 'var/tmp/test/reef_upload_value';
	
	public function declarationProvider() {
		
		yield 'decl1' => [
			'declaration' => [
				'component' => 'reef:upload',
				'name' => 'upload_field',
				'required' => true,
				'types' => [
					'txt' => true,
				],
				'locale' => [
					'title' => 'The title'
				],
			],
			'valid_values' => [
				function() {
					$this->uploadFile('file.txt', 'somecontent', $s_uuid);
					return [$s_uuid];
				},
			],
			'invalid_values' => [
				[],
				['some-invalid-value'],
			],
		];
		
		yield 'decl2' => [
			'declaration' => [
				'component' => 'reef:upload',
				'name' => 'upload_field',
				'multiple' => true,
				'max_files' => 2,
				'types' => [
					'txt' => true,
				],
				'locale' => [
					'title' => 'The title'
				],
			],
			'valid_values' => [
				function() {
					$this->uploadFile('file1.txt', 'somecontent', $s_uuid1);
					$this->uploadFile('file2.txt', 'somecontent', $s_uuid2);
					return [$s_uuid1, $s_uuid2];
				},
			],
			'invalid_values' => [
				function() {
					$this->uploadFile('file1.txt', 'somecontent', $s_uuid1);
					$this->uploadFile('file2.txt', 'somecontent', $s_uuid2);
					$this->uploadFile('file3.txt', 'somecontent', $s_uuid3);
					return [$s_uuid1, $s_uuid2, $s_uuid3];
				},
				function() {
					$this->uploadFile('file1.txt', 'somecontent', $s_uuid1);
					return [$s_uuid1, 'some-invalid-value'];
				},
				function() {
					// Smallest valid PNG file according to https://github.com/mathiasbynens/small
					// We test here that a file will get uploaded (PNG is in the default list of extensions), but will not get accepted by the uploader (only .txt is allowed)
					$s_content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACklEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==');
					$this->uploadFile('file.png', $s_content, $s_uuid);
					return [$s_uuid];
				},
			],
		];
		
	}
	
	public function testCanBeCreated() {
		parent::testCanBeCreated();
	}
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testUploadInvalidFile() {
		$a_result = $this->uploadFile('file.png', 'just-plain-text');
		$this->assertFalse($a_result['success']);
	}
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testGetDefaultTypes() {
		$this->assertInternalType('array', static::$Component->getDefaultTypes());
	}
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testReSubmit() {
		$a_declaration = [
			'component' => 'reef:upload',
			'name' => 'upload_field',
			'multiple' => true,
			'max_files' => 3,
			'types' => [
				'txt' => true,
			],
			'locale' => [
				'title' => 'The title'
			],
		];
		
		$this->uploadFile('file1.txt', 'somecontent', $s_uuid1);
		$this->uploadFile('file2.txt', 'somecontent', $s_uuid2);
		$this->uploadFile('file3.txt', 'somecontent', $s_uuid3);
		$this->uploadFile('file4.txt', 'somecontent', $s_uuid4);
		$this->uploadFile('file5.txt', 'somecontent', $s_uuid5);
		
		$Form = static::$Reef->newTempForm();
		$Submission = $Form->newSubmission();
		$Field = static::$Component->newField($a_declaration, $Form);
		$Filesystem = static::$Reef->getDataStore()->getFilesystem();
		
		$Value = $Field->newValue($Submission);
		$this->assertSame(0, count($Value->getFiles()));
		$this->assertSame(5, $Filesystem->numFilesInContext('upload'));
		
		$Value->fromUserInput([$s_uuid1, $s_uuid2]);
		$this->assertTrue($Value->validate());
		$this->assertSame(2, count($Value->getFiles()));
		$this->assertSame(3, $Filesystem->numFilesInContext('upload'));
		
		$Value->fromUserInput([$s_uuid1, $s_uuid2, '']);
		$this->assertTrue($Value->validate());
		$this->assertSame(2, count($Value->getFiles()));
		$this->assertSame(3, $Filesystem->numFilesInContext('upload'));
		
		$Value->fromUserInput(['x'.$s_uuid1, $s_uuid2, $s_uuid3]);
		$this->assertTrue($Value->validate());
		$this->assertSame(2, count($Value->getFiles()));
		$this->assertSame(2, $Filesystem->numFilesInContext('upload'));
		
		$Value->fromUserInput([]);
		$this->assertTrue($Value->validate());
		$this->assertSame(0, count($Value->getFiles()));
		
		$Value->fromUserInput([$s_uuid4, 'x'.$s_uuid5]);
		$this->assertTrue($Value->validate());
		$this->assertSame(1, count($Value->getFiles()));
		$this->assertSame(0, $Filesystem->numFilesInContext('upload'));
	}
	
	/**
	 * @depends testCanBeCreated
	 */
	public function testDeleteField() {
		$Form = static::$Reef->newStoredForm([
			'storage_name' => \Reef\unique_id(),
			'fields' => [
				[
					'component' => 'reef:upload',
					'name' => 'field1',
					'multiple' => true,
					'max_files' => 3,
					'types' => [
						'txt' => true,
					],
					'locale' => [
						'title' => 'The title'
					],
				],
				[
					'component' => 'reef:upload',
					'name' => 'field2',
					'multiple' => true,
					'max_files' => 3,
					'types' => [
						'txt' => true,
					],
					'locale' => [
						'title' => 'The title'
					],
				],
			],
		]);
		
		$this->uploadFile('file1.txt', 'somecontent', $s_uuid1);
		$this->uploadFile('file2.txt', 'somecontent', $s_uuid2);
		$this->uploadFile('file3.txt', 'somecontent', $s_uuid3);
		$this->uploadFile('file4.txt', 'somecontent', $s_uuid4);
		$this->uploadFile('file5.txt', 'somecontent', $s_uuid5);
		
		$Filesystem = static::$Reef->getDataStore()->getFilesystem();
		
		$Submission = $Form->newSubmission();
		$Submission->emptySubmission();
		$Submission->save();
		$Value1 = $Submission->getFieldValue('field1');
		$Value2 = $Submission->getFieldValue('field2');
		
		// Start: only uploaded, not assigned
		$this->assertSame(0, count($Value1->getFiles()));
		$this->assertSame(0, count($Value2->getFiles()));
		$this->assertSame(0, $Filesystem->numFilesInContext($Submission));
		$this->assertSame(5, $Filesystem->numFilesInContext('upload'));
		
		// Assign files
		$Value1->fromUserInput([$s_uuid1, $s_uuid2]);
		$Value2->fromUserInput([$s_uuid3, $s_uuid4]);
		$Submission->save();
		
		$this->assertSame(2, count($Value1->getFiles()));
		$this->assertSame(2, count($Value2->getFiles()));
		$this->assertSame(4, $Filesystem->numFilesInContext($Submission));
		$this->assertSame(1, $Filesystem->numFilesInContext('upload'));
		
		// Remove one field
		$Form->newCreator()->getFieldByName('field1')->delete()->apply();
		
		$this->assertSame(2, count($Value2->getFiles()));
		$this->assertSame(2, $Filesystem->numFilesInContext($Submission));
		$this->assertSame(1, $Filesystem->numFilesInContext('upload'));
		
		// Delete form
		$Form->delete();
	}
}
