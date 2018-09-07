<?php

require_once('./common.php');

// Find whether we are given an existing form id
if(isset($_POST['form_id']) && $_POST['form_id'] > 0) {
	$Form = $Reef->getForm($_POST['form_id']);
}
else if(isset($_GET['form_id']) && $_GET['form_id'] > 0) {
	$Form = $Reef->getForm($_GET['form_id']);
}
else {
	$Form = $Reef->newTempStoredForm([
		'storage_name' => 'form_'.$Reef->getFormStorage()->next(),
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

if(isset($_POST['builder_data'])) {
	$Builder->processBuilderData_write($Form, $_POST['builder_data'], function(&$a_return) {
		if($a_return['result']) {
			$a_return['redirect'] = 'index.php';
		}
	});
}

$s_html = $Builder->generateBuilderHtml($Form);

$s_CSS = $Reef->getReefAssets()->getCSSHTML('builder');
$s_JS = $Reef->getReefAssets()->getJSHTML('builder');

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
		builder = new ReefBuilder('.builderWrapper', {
			submit_before : function(ajaxParams) {
				ajaxParams.data.form_id = <?php echo(($Form instanceof \Reef\Form\StoredForm) ? (int)$Form->getFormId() : -1); ?>;
			}
		});
	});
	</script>
</head>
<body>
<div class="builderWrapper">
	<?php echo($s_html); ?>
</div>
</body>
</html>
