<?php

namespace ReefTests\integration\Components\Upload;

use \ReefTests\integration\Components\ComponentTestCase;

final class UploadComponentTest extends ComponentTestCase {
	
	use CommonUploadTrait;
	
	const FILES_DIR = 'var/tmp/test/reef_upload_component';
	
}
