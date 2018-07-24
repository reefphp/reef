<?php

require_once('./common.php');

$Form = $Reef->newTempForm();
$Form->importDeclaration($_SESSION['sandbox']['declaration']);

// Process a POST request
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$Submission = $Form->newSubmission();
	$Submission->fromUserInput($_POST['form_data']);
	if($Submission->validate()) {
		var_dump($Submission->toStructured());
		die();
	}
}

$s_form = $Form->generateFormHtml(null, ['main_var' => 'form_data']);

$s_CSS = $Form->getFormAssets()->getCSSHTML(function($s_assetsHash) {
	return './assets.php?type=css&amp;hash='.$s_assetsHash;
});
$s_JS = $Form->getFormAssets()->getJSHTML(function($s_assetsHash) {
	return './assets.php?type=js&amp;hash='.$s_assetsHash;
});

echo($s_form);
