<?php

require_once('./common.php');

// Generate the form object from the declaration
if(count($Reef->getFormIds()) == 0) {
	$Form = $Reef->newForm();
	$Form->newDeclarationFromFile('./declaration.yml');
}

$Form = $Reef->getForm(1);

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
	if($Submission->validate()) {
		$Submission->save();
	}
	$b_load = true;
}

if($b_load) {
	// If $b_load is true, we should display an existing submission
	$i_submissionId = $Submission->getSubmissionId();
	$s_form = $Form->generateFormHtml($Submission, ['main_var' => 'form_data']);
}
else {
	// Else, we display the form for adding a new submission
	$i_submissionId = -1;
	$s_form = $Form->generateFormHtml(null, ['main_var' => 'form_data']);
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
<ul>
	<li><a href="?">New submission</a></li>
<?php
foreach($Form->getSubmissionIds() as $i_id) {
	echo('<li><a href="?submission_id='.$i_id.'">Submission '.$i_id.'</a></li>');
}
?>
</ul>
<form action="" method="post" onsubmit="return reef.validate();">
	<input type="hidden" name="submission_id" value="<?php echo($i_submissionId); ?>" />
	<div class="form-wrapper">
		<?php echo($s_form); ?>
	</div>
	<input type="submit" name="submit" value="submit" class="btn btn-primary" />
<?php
if(!$Submission->isNew()) {
	?>
	<input type="submit" name="delete" value="delete" class="btn btn-outline-danger" />
	<?php
}
?>
</form>
<script>
var reef;
$(function() {
	reef = new Reef($('.form-wrapper'));
});
</script>
</body>
</html>
