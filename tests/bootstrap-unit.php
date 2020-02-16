<?php

session_start();

require_once(__DIR__ . '/../vendor/autoload.php');

define('TEST_TMP_DIR', getenv('IN_DOCKER') ? '/var/tmp/test' :  __DIR__ . '/../var/tmp/test');
