<?php

require_once('./common.php');

// Generate the form object from the declaration
$Form = $Reef->newForm();

// Find whether we are given an existing form id
if(isset($_POST['form_id']) && $_POST['form_id'] > 0) {
	$Form = $Reef->getForm($_POST['form_id']);
}
else if(isset($_GET['form_id']) && $_GET['form_id'] > 0) {
	$Form = $Reef->getForm($_GET['form_id']);
}
else {
	if(!is_dir('./storage/submissions/')) {
		mkdir('./storage/submissions/', 0755, true);
	}
	
	$Form = $Reef->newForm();
	$Form->importDeclaration([
		'submissions' => [
			'type' => 'JSON',
			'path' => './storage/submissions/',
		],
		'main_var' => 'form_data',
		'layout' => [
			'name' => 'bootstrap4',
			'col_left' => 'col-12 col-md-3',
			'col_right' => 'col-12 col-md-9',
		],
	]);
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

if(isset($_POST['form_fields'])) {
	$a_return = $Builder->applyBuilderData($Form, $_POST['form_fields']);
	$Form->save();
	
	$a_return['redirect'] = 'index.php';
	
	echo json_encode($a_return);
	die();
}

$s_html = $Builder->generateBuilderHtml($Form);

$s_CSS = $Reef->getReefAssets()->getCSSHTML(function($s_assetsHash) {
	return './assets.php?type=css&amp;hash='.$s_assetsHash;
});
$s_JS = $Reef->getReefAssets()->getJSHTML(function($s_assetsHash) {
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
	
	<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
	
	<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.7.0/Sortable.min.js" integrity="sha256-+BvLlLgWJALRwV4lbCh0i4zqHhDqxR8FKUJmIl/u/vQ=" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/mustache.js/2.3.0/mustache.min.js" integrity="sha256-iaqfO5ue0VbSGcEiQn+OeXxnxAMK2+QgHXIDA5bWtGI=" crossorigin="anonymous"></script>

	<?php echo($s_JS); ?>
	
	<title>Form builder</title>
</head>
<body>
<?php echo($s_html); ?>

</body>
</html>
