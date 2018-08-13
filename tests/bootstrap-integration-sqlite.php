<?php

require_once(__DIR__ . '/../vendor/autoload.php');

$_reef_PDO = new \PDO("sqlite::memory:");

$_reef_setup = new \Reef\ReefSetup(
	\Reef\Storage\PDOStorageFactory::createFactory($_reef_PDO),
	new \Reef\Layout\bootstrap4\bootstrap4()
);

$_reef_reef = new \Reef\Reef(
	$_reef_setup,
	[]
);
