<?php

ini_set('display_errors', 'on'); error_reporting(E_ALL);

require('../../vendor/autoload.php');

$Reef = new Reef\Reef(
	new Reef\Storage\JSONStorageFactory('./storage/'),
	[
		'cache_dir' => './cache/',
	]
);
