<?php

require_once(__DIR__ . '/../vendor/autoload.php');

$DB_NAME = 'reef_test';
$DB_HOST = '127.0.0.1';
$DB_USER = 'reef_test';
$DB_PASS = 'reef_test';

$_reef_PDO = new \PDO("mysql:dbname=".$DB_NAME.";host=".$DB_HOST, $DB_USER, $DB_PASS);

$_reef_setup = new \Reef\ReefSetup(
	\Reef\Storage\PDOStorageFactory::createFactory($_reef_PDO),
	new \Reef\Layout\bootstrap4\bootstrap4()
);

$_reef_reef = new \Reef\Reef(
	$_reef_setup,
	[]
);
