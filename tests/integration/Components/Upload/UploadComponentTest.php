<?php

namespace tests\Components;

require_once(__DIR__ . '/../ComponentTestCase.php');
require_once(__DIR__ . '/CommonUploadTrait.php');

final class UploadComponentTest extends ComponentTestCase {
	
	use CommonUploadTrait;
	
	const FILES_DIR = 'var/tmp/test/reef_upload_component';
	
}
