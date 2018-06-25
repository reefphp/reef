<?php

ini_set('display_errors', 'on'); error_reporting(E_ALL);

require('../../vendor/autoload.php');

// Create Reef object. In this example, we do not safe the form itself, so we use NoStorage
$Reef = new Reef\Reef(
	new Reef\Storage\NoStorage(),
	[
		'cache_dir' => './cache/',
	]
);
