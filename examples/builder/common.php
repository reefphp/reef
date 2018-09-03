<?php

ini_set('display_errors', 'on'); error_reporting(E_ALL);

session_start();

require('../../vendor/autoload.php');

$PDO = new \PDO("sqlite:storage/reef.db");

// Specify which components we want to use
$Setup = new \Reef\ReefSetup(
	\Reef\Storage\PDOStorageFactory::createFactory($PDO),
	new Reef\Layout\bootstrap4\bootstrap4(),
	new \Reef\Session\PhpSession()
);
$Setup->addComponent(new \Reef\Components\Upload\UploadComponent);

$Reef = new Reef\Reef(
	$Setup,
	[
		'cache_dir' => './cache/',
		'locales' => ['en_US', 'nl_NL'],
		'internal_request_url' => './reefrequest.php?hash=[[request_hash]]',
		'files_dir' => './storage/files',
	]
);
