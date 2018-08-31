<?php

namespace tests\Components;

require_once(__DIR__ . '/../FieldTestCase.php');
require_once(__DIR__ . '/CommonUploadTrait.php');

final class UploadFieldTest extends FieldTestCase {
	
	use CommonUploadTrait;
	
	const FILES_DIR = 'var/tmp/test/reef_upload_field';
	
}
