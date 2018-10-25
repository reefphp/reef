<?php

session_start();

require_once(__DIR__ . '/../vendor/autoload.php');

if(getenv('IN_SCRUTINIZER_CI')) {
	$DB_NAME = 'reef_test';
	$DB_HOST = '127.0.0.1';
	$DB_USER = 'root';
	$DB_PASS = '';
}
else {
	$DB_NAME = 'reef_test';
	$DB_HOST = getenv('IN_GITLAB_CI') ? 'mysql' : '127.0.0.1';
	$DB_USER = 'reef_test';
	$DB_PASS = 'reef_test';
}

$_reef_PDO = new \PDO("mysql:dbname=".$DB_NAME.";host=".$DB_HOST.";charset=utf8mb4", $DB_USER, $DB_PASS);
$_reef_PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$_reef_setup = new \Reef\ReefSetup(
	\Reef\Storage\PDOStorageFactory::createFactory($_reef_PDO),
	new \Reef\Layout\bootstrap4\bootstrap4(),
	new \Reef\Session\TmpSession()
);

$_reef_reef = new \Reef\Reef(
	$_reef_setup,
	[
	]
);
