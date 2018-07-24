<?php

ini_set('display_errors', 'on'); error_reporting(E_ALL);

session_start();

require('../../vendor/autoload.php');

// Specify which components we want to use
$Setup = new \Reef\ReefSetup(
	new Reef\Storage\NoStorageFactory(),
	new Reef\Layout\bootstrap4()
);

$Reef = new Reef\Reef(
	$Setup,
	[
		'cache_dir' => './cache/',
		'locales' => ['en_US', 'nl_NL'],
	]
);

if(!isset($_SESSION['sandbox'])) {
	$_SESSION['sandbox'] = [
		'declaration' => [],
	];
}
