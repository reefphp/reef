<?php

require_once('./common.php');

$Form = $Reef->newTempForm();
$Form->importDefinition($_SESSION['sandbox']['definition']);

// Process a POST request
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$Submission = $Form->newSubmission();
	$Submission->fromUserInput($_POST['form_data']??[]);
	if($Submission->validate()) {
		$a_return = [
			'data' => $Submission->toStructured(),
		];
	}
	else {
		$a_return = [
			'errors' => $Submission->getErrors(),
		];
	}
	
	echo(json_encode($a_return));
	die();
}

$s_form = $Form->generateFormHtml(null, ['main_var' => 'form_data']);

$s_CSS = $Form->getFormAssets()->getCSSHTML(function($s_assetsHash) {
	return './assets.php?type=css&amp;hash='.$s_assetsHash;
});
$s_JS = $Form->getFormAssets()->getJSHTML(function($s_assetsHash) {
	return './assets.php?type=js&amp;hash='.$s_assetsHash;
});

echo($s_form);
