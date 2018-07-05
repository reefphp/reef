<?php

ini_set('display_errors', 'on'); error_reporting(E_ALL);

require('../../vendor/autoload.php');

if(!is_dir('./storage/forms/')) {
	mkdir('./storage/forms/', 0755, true);
}
$Reef = new Reef\Reef(
	new Reef\Storage\JSONStorage('./storage/forms/'),
	[
		'cache_dir' => './cache/',
	]
);

// Specify which components we want to use
$Mapper = $Reef->getComponentMapper();
$Mapper->add(new Reef\Components\SingleLineText\SingleLineTextComponent);
$Mapper->add(new Reef\Components\SingleCheckbox\SingleCheckboxComponent);
$Mapper->add(new Reef\Components\TextNumber\TextNumberComponent);
$Mapper->add(new Reef\Components\Heading\HeadingComponent);
