<?php

require_once('./common.php');

if(isset($_POST['form_id']) && $_POST['form_id'] > 0) {
	$Form = $Reef->getForm($_POST['form_id']);
}
else if(isset($_GET['form_id']) && $_GET['form_id'] > 0) {
	$Form = $Reef->getForm($_GET['form_id']);
}
else {
	header("Location: ./index.php");
	exit();
}

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

if(isset($_GET['mode']) && $_GET['mode'] == 'delete') {
	$Submission->delete();
	header("Location: index.php");
	exit();
}
$b_view = (isset($_GET['mode']) && $_GET['mode'] == 'view');

// Process a POST request
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$Submission->fromUserInput($_POST['form_data']??[]);
	if($Submission->validate()) {
		$Submission->save();
		header("Location: submission.php?form_id=".$Form->getFormId()."&submission_id=".$Submission->getSubmissionId());
	}
}

if($b_load) {
	// If $b_load is true, we should display an existing submission
	$i_submissionId = $Submission->getSubmissionId();
	if($b_view) {
		$s_form = $Form->generateSubmissionHtml($Submission, []);
	}
	else {
		$s_form = $Form->generateFormHtml($Submission, ['main_var' => 'form_data']);
	}
}
else {
	// Else, we display the form for adding a new submission
	$i_submissionId = -1;
	$s_form = $Form->generateFormHtml(null, ['main_var' => 'form_data']);
}

$s_CSS = $Form->getFormAssets()->getCSSHTML();
$s_JS = $Form->getFormAssets()->getJSHTML();

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	
	<?php echo($s_CSS); ?>
	
	<?php echo($s_JS); ?>
	
	<title>Builder form</title>
</head>
<body>
<div class="m-2 ml-3">
	<a href="index.php">&laquo; Terug</a>
	<?php
	if($b_view) {
	?>
	<a class="ml-3" href="submission.php?form_id=<?php echo($Form->getFormId()); ?>&amp;submission_id=<?php echo($i_submissionId); ?>">Edit</a>
	<?php
	} else {
	?>
	<a class="ml-3" href="submission.php?form_id=<?php echo($Form->getFormId()); ?>&amp;submission_id=<?php echo($i_submissionId); ?>&amp;mode=view">View</a>
	<?php
	}
	?>
</div>
<?php
if($b_view) {
?>
<div class="form-wrapper">
	<?php echo($s_form); ?>
</div>
<?php
} else {
?>
<form action="" method="post" onsubmit="return reef.validate();">
	<input type="hidden" name="submission_id" value="<?php echo($i_submissionId); ?>" />
	<div class="form-wrapper">
		<?php echo($s_form); ?>
	</div>
	<input type="submit" name="submit" value="submit" class="btn btn-primary" />
</form>
<script>
var reef;
$(function() {
	reef = new Reef($('.form-wrapper'));
});
</script>
<?php
}
?>
</body>
</html>
