<?php

require_once('./common.php');

if($_GET['type'] == 'js') {
	header('Content-type: text/javascript');
}
else if($_GET['type'] == 'css') {
	header('Content-type: text/css');
}

header('Cache-Control: public, max-age=31536000');

echo $Reef->getAsset($_GET['type'], $_GET['hash']);
