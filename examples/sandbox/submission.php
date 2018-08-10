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

$DisplayForm = $Form->tempDuplicate();
$DisplayForm->newCreator()->addField('reef:submit')->apply();

$s_form = $DisplayForm->generateFormHtml(null, ['main_var' => 'form_data']);

echo($s_form);
