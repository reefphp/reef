<?php

session_start();

require_once(__DIR__ . '/../vendor/autoload.php');

define('TEST_TMP_DIR', getenv('IN_DOCKER') ? '/var/tmp/test' :  __DIR__ . '/../var/tmp/test');

$_reef_PDO = new \PDO("sqlite::memory:");
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
