<?php

namespace tests\Components;

require_once(__DIR__ . '/../ConditionTestCase.php');
require_once(__DIR__ . '/CommonUploadTrait.php');

final class UploadConditionTest extends ConditionTestCase {
	
	use CommonUploadTrait;
	
	const FILES_DIR = 'var/tmp/test/reef_upload_condition';
	
	public function declarationProvider() {
		
		yield 'decl2' => [
			'declaration' => [
				'component' => 'reef:upload',
				'name' => 'name',
				'multiple' => true,
				'types' => [
					'txt' => true,
				],
				'locale' => [
					'title' => 'The title'
				],
			],
			'valid_conditions' => [
				[
					'condition' => 'name is empty',
					'true_for' => [
						[],
					],
					'false_for' => [
						function() {
							$this->uploadFile('file1.txt', 'somecontent', $s_uuid1);
							$this->uploadFile('file2.txt', 'somecontent', $s_uuid2);
							return [$s_uuid1, $s_uuid2];
						},
					],
				],
				[
					'condition' => 'name is not empty',
					'true_for' => [
						function() {
							$this->uploadFile('file1.txt', 'somecontent', $s_uuid1);
							return [$s_uuid1];
						},
					],
					'false_for' => [
						[],
					],
				],
			],
			'invalid_conditions' => [
				'name is empty "asdf"'
			],
		];
		
	}
	
}
