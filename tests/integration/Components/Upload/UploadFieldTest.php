<?php

namespace ReefTests\integration\Components\Upload;

use \ReefTests\integration\Components\FieldTestCase;

final class UploadFieldTest extends FieldTestCase {
	
	use CommonUploadTrait;
	
	const FILES_DIR = TEST_TMP_DIR . '/reef_upload_field';
	
}
