<?php

require_once('./common.php');

// Specify which components we want to use
$Mapper = $Reef->getComponentMapper();
$Mapper->add('Reef\\Components\\SingleLineText\\SingleLineText');

// Generate the form object from the declaration
$Form = $Reef->newForm();
$Form->importDeclarationFile('./declaration.yml');

// Find whether we are given an existing submission id
if(isset($_POST['submission_id']) && $_POST['submission_id'] > 0) {
	$Submission = $Form->getSubmission($_POST['submission_id']);
	$b_load = true;
}
else if(isset($_GET['submission_id']) && $_GET['submission_id'] > 0) {
	$Submission = $Form->getSubmission($_GET['submission_id']);
	$b_load = true;
}
else {
	$Submission = $Form->newSubmission();
	$b_load = false;
}

// Process a POST request
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	if(isset($_POST['delete'])) {
		$Submission->delete();
		header("Location: index.php");
		exit();
	}
	
	$Submission->fromUserInput($_POST['form_data']);
	$Submission->save();
	$b_load = true;
}

if($b_load) {
	// If $b_load is true, we should display an existing submission
	$i_submissionId = $Submission->getSubmissionId();
	$s_form = $Form->generateFormHtml($Submission);
}
else {
	// Else, we display the form for adding a new submission
	$i_submissionId = -1;
	$s_form = $Form->generateFormHtml();
}

$s_CSS = $Form->getFormAssets()->getCSSHTML(function($s_assetsHash) {
	return './assets.php?type=css&amp;hash='.$s_assetsHash;
});
$s_JS = $Form->getFormAssets()->getJSHTML(function($s_assetsHash) {
	return './assets.php?type=js&amp;hash='.$s_assetsHash;
});

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	
	<?php echo($s_CSS); ?>
	
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

	<?php echo($s_JS); ?>
	
	<title>Simple form</title>
</head>
<body>
<div class="container-fluid">
<ul>
	<li><a href="?">New submission</a></li>
<?php
foreach($Form->getSubmissionIds() as $i_id) {
	echo('<li><a href="?submission_id='.$i_id.'">Submission '.$i_id.'</a></li>');
}
?>
</ul>
<form action="" method="post">
	<input type="hidden" name="submission_id" value="<?php echo($i_submissionId); ?>" />
	<?php echo($s_form); ?>
<input type="submit" name="submit" value="submit" class="btn btn-primary" />
<?php
if($b_load) {
	?>
	<input type="submit" name="delete" value="delete" class="btn btn-outline-danger" />
	<?php
}
?>
</form>
</div>
</body>
</html>