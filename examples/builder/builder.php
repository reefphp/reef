<?php

require_once('./common.php');

// Find whether we are given an existing form id
if(isset($_POST['form_data']['form_id']) && $_POST['form_data']['form_id'] > 0) {
	$Form = $Reef->getForm($_POST['form_data']['form_id']);
}
else if(isset($_GET['form_id']) && $_GET['form_id'] > 0) {
	$Form = $Reef->getForm($_GET['form_id']);
}
else {
	$Form = $Reef->newStoredForm();
	$Form->importDefinition([
		'storage_name' => 'form_'.$Form->getReef()->getFormStorage()->next(),
	]);
}

if(isset($_GET['mode']) && $_GET['mode'] == 'download') {
	$Form->getSubmissionTable()->streamCSV();
}

if(isset($_GET['mode']) && $_GET['mode'] == 'delete') {
	$Form->delete();
	header("Location: index.php");
	exit();
}

$Builder = $Reef->getBuilder();
$Builder->setSettings([
	'submit_action' => 'builder.php',
]);

if(isset($_POST['form_data'])) {
	try {
		if($_POST['mode'] == 'apply') {
			$a_return = $Builder->applyBuilderData($Form, $_POST['form_data']);
			$Form->save();
			
			$a_return['redirect'] = 'index.php';
		}
		else {
			$a_return = [
				'dataloss' => $Builder->checkBuilderDataLoss($Form, $_POST['form_data']),
			];
		}
	}
	catch(\Reef\Exception\ValidationException $e) {
		$a_return = [
			'errors' => $e->getErrors(),
		];
	}
	
	echo json_encode($a_return);
	die();
}

$s_html = $Builder->generateBuilderHtml($Form);

$s_CSS = $Reef->getReefAssets()->getCSSHTML();
$s_JS = $Reef->getReefAssets()->getJSHTML();

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	
	<?php echo($s_CSS); ?>
	
	<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
	
	<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.7.0/Sortable.min.js" integrity="sha256-+BvLlLgWJALRwV4lbCh0i4zqHhDqxR8FKUJmIl/u/vQ=" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/mustache.js/2.3.0/mustache.min.js" integrity="sha256-iaqfO5ue0VbSGcEiQn+OeXxnxAMK2+QgHXIDA5bWtGI=" crossorigin="anonymous"></script>

	<?php echo($s_JS); ?>
	
	<title>Form builder</title>
	<script>
	$(function() {
		var builder = new ReefBuilder('.builderWrapper');
	});
	</script>
</head>
<body>
<div class="builderWrapper">
	<?php echo($s_html); ?>
</div>
</body>
</html>
