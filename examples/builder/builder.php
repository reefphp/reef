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
	$Form->newSubmissionOverview()->streamCSV();
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

$s_CSS = $Reef->getReefAssets()->getCSSHTML(['builder' => true]);
$s_JS = $Reef->getReefAssets()->getJSHTML(['builder' => true]);

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	
	<?php echo($s_CSS); ?>
	
	<?php echo($s_JS); ?>
	
	<title>Form builder</title>
	<script>
	var builder;
	$(function() {
		builder = new ReefBuilder('.builderWrapper');
	});
	</script>
</head>
<body>
<div class="builderWrapper">
	<?php echo($s_html); ?>
</div>
</body>
</html>
