<?php

ini_set('display_errors', 'on'); error_reporting(E_ALL);

session_start();

require('../../vendor/autoload.php');

// Specify which components we want to use
$Setup = new \Reef\ReefSetup(
	new Reef\Storage\NoStorageFactory(),
	new Reef\Layout\bootstrap4\bootstrap4()
);
$Setup->addComponent(new \Reef\Components\Upload\UploadComponent);

$a_locales = ['en_US', 'nl_NL'];
if(isset($_GET['locale']) && ($i_pos = array_search($_GET['locale'], $a_locales)) !== false) {
	array_splice($a_locales, $i_pos, 1);
	array_unshift($a_locales, $_GET['locale']);
}

$Reef = new Reef\Reef(
	$Setup,
	[
		'cache_dir' => './cache/',
		'locales' => $a_locales,
		'internal_request_url' => './reefrequest.php?hash=[[request_hash]]',
		'files_dir' => './storage/files',
	]
);

if(!isset($_SESSION['sandbox'])) {
	$_SESSION['sandbox'] = [
		'definition' => [],
	];
}
