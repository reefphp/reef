#!/usr/bin/env php
<?php

if ($argc == 0) {
	die("Usage: php runner.php [phpunit config]");
}

$s_baseDir = realpath(__DIR__ . '/../').'/';

require $s_baseDir . 'vendor/autoload.php';

$s_coverageDir = 'var/coverage/';

if(!is_dir($s_baseDir.$s_coverageDir)) {
	mkdir('./'.$s_coverageDir, 0755, true);
}

$a_configFiles = [];
$b_coverageHtml = false;

for($i=1; $i<$argc; $i++) {
	$s_arg = $argv[$i];
	
	if($s_arg == '--coverage-html') {
		$b_coverageHtml = true;
		continue;
	}
	
	if(file_exists($s_baseDir.'tests/phpunit-'.$s_arg.'.xml')) {
		$a_configFiles[] = 'phpunit-'.$s_arg.'.xml';
	}
}


$a_availableConfigFiles = [];
$iterator = new GlobIterator($s_baseDir . 'tests/phpunit-*.xml', FilesystemIterator::KEY_AS_FILENAME);
foreach ($iterator as $item) {
	$a_availableConfigFiles[] = $item->getFilename();
}

if(empty($a_configFiles)) {
	$a_configFiles = $a_availableConfigFiles;
}
else {
	$a_configFiles = array_unique(array_intersect($a_configFiles, $a_availableConfigFiles));
}

if(empty($a_configFiles)) {
	print "Empty config files list".PHP_EOL;
	die();
}

foreach ($a_configFiles as $s_configFile) {
	print "Running test '".$s_configFile."'...".PHP_EOL.PHP_EOL;
	
	$s_command = 'vendor/bin/phpunit --configuration tests/'.$s_configFile;
	
	if($b_coverageHtml) {
		$s_command .= ' --coverage-php '.$s_baseDir.$s_coverageDir.$s_configFile.'.cov';
	}
	
	passthru($s_command, $i_return_var);
	
	if($i_return_var) {
		print "Test failed".PHP_EOL;
		die();
	}
}

if($b_coverageHtml) {
	// https://stackoverflow.com/questions/10167775/aggregating-code-coverage-from-several-executions-of-phpunit/14874892#14874892
	
	foreach ($a_configFiles as $s_configFile) {
		
		// See PHP_CodeCoverage_Report_PHP::process
		// @var PHP_CodeCoverage
		$cov = include($s_baseDir.$s_coverageDir.$s_configFile.'.cov');
		if (isset($codeCoverage)) {
			$codeCoverage->filter()->addFilesToWhitelist($cov->filter()->getWhitelist());
			$codeCoverage->merge($cov);
		} else {
			$codeCoverage = $cov;
		}
	}

	print "\nGenerating code coverage report in HTML format ...";

	// Based on PHPUnit_TextUI_TestRunner::doRun
	$writer = new \SebastianBergmann\CodeCoverage\Report\Html\Facade();

	$writer->process($codeCoverage, $s_baseDir.$s_coverageDir);

	print " done\n";
}
