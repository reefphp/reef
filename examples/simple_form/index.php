<?php

require_once('./common.php');

// Generate the form object from the definition
if(count($Reef->getFormIds()) == 0) {
	$Form = $Reef->newStoredForm();
	$Form->newDefinitionFromFile('./definition.yml');
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

$DisplayForm = $Form->tempDuplicate();
$DisplayForm->newCreator()->addField('reef:submit')->apply();

if($b_load) {
	// If $b_load is true, we should display an existing submission
	$i_submissionId = $Submission->getSubmissionId();
	$s_form = $DisplayForm->generateFormHtml($Submission, ['main_var' => 'form_data']);
}
else {
	// Else, we display the form for adding a new submission
	$i_submissionId = -1;
	$s_form = $DisplayForm->generateFormHtml(null, ['main_var' => 'form_data']);
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
<?php
if(!$Submission->isNew()) {
	?>
	<div class="container-fluid">
		<div class="row form-group">
			<div class="col">
				<input type="submit" name="delete" value="delete" class="btn btn-outline-danger" />
			</div>
		</div>
	</div>
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
