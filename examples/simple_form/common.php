<?php

ini_set('display_errors', 'on'); error_reporting(E_ALL);

require('../../vendor/autoload.php');

$PDO = new \PDO("sqlite:storage/reef.db");

// Specify which components we want to use
$Setup = new \Reef\ReefSetup(
	\Reef\Storage\PDOStorageFactory::createFactory($PDO),
	new Reef\Layout\bootstrap4\bootstrap4()
);

$Reef = new Reef\Reef(
	$Setup,
	[
		'cache_dir' => './cache/',
		'assets_url' => './assets.php?hash=[[assets_hash]]',
	]
);
