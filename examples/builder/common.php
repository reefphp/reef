<?php

ini_set('display_errors', 'on'); error_reporting(E_ALL);

require('../../vendor/autoload.php');

// Specify which components we want to use
$Setup = new \Reef\ReefSetup(
	new Reef\Storage\JSONStorageFactory('./storage/'),
	new Reef\Layout\bootstrap4()
);

$Setup->addComponent(new Reef\Components\SingleLineText\SingleLineTextComponent);
$Setup->addComponent(new Reef\Components\SingleCheckbox\SingleCheckboxComponent);
$Setup->addComponent(new Reef\Components\TextNumber\TextNumberComponent);
$Setup->addComponent(new Reef\Components\Heading\HeadingComponent);

$Reef = new Reef\Reef(
	$Setup,
	[
		'cache_dir' => './cache/',
	]
);
