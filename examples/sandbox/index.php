<?php

require_once('./common.php');

$Form = $Reef->newTempForm();
$Form->importDefinition($_SESSION['sandbox']['definition']);

$Builder = $Reef->getBuilder();
$Builder->setSettings([
	'submit_action' => 'index.php',
]);

if(isset($_POST['form_data'])) {
	if($_POST['mode'] == 'apply') {
		$a_return = $Builder->applyBuilderData($Form, $_POST['form_data']);
		
		$_SESSION['sandbox']['definition'] = $Form->generateDefinition();
		
		$a_return['definition'] = \Symfony\Component\Yaml\Yaml::dump($_SESSION['sandbox']['definition'], 5);
	}
	else {
		try {
			$a_return = [
				'dataloss' => $Builder->checkBuilderDataLoss($Form, $_POST['form_data']),
			];
		}
		catch(\Reef\Exception\ValidationException $e) {
			$a_return = [
				'errors' => [
					$e->getMessage(),
				],
			];
		}
	}
	
	echo json_encode($a_return);
	die();
}

if(isset($_POST['definition'])) {
	try {
		$Form->importDefinitionString($_POST['definition']);
		
		$_SESSION['sandbox']['definition'] = $Form->generateDefinition();
		
		$a_return['definition'] = \Symfony\Component\Yaml\Yaml::dump($_SESSION['sandbox']['definition'], 5);
	}
	catch(\Reef\Exception\ValidationException $e) {
		$a_return = [
			'errors' => [
				$e->getMessage(),
			],
		];
	}
	
	echo json_encode($a_return);
	die();
}

$s_html = $Builder->generateBuilderHtml($Form);

if(isset($_GET['builder_only'])) {
	echo($s_html);
	die();
}

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
	
	<script src="https://unpkg.com/split.js/split.min.js"></script>
	
	<?php echo($s_JS); ?>
	
	<title>Form sandbox</title>
<script>
var reef;

$(function() {
	Split(['#sandbox-left', '#sandbox-right']);
	
	Split(['#sandbox-top-left', '#sandbox-bottom-left'], {
		direction: 'vertical'
	});
	
	Split(['#sandbox-top-right', '#sandbox-bottom-right'], {
		direction: 'vertical'
	});
	
	
	var fn_loadForm = function() {
		$.ajax({
			url: 'submission.php',
			method: 'get',
			success: function(response) {
				$('.form-wrapper').html(response);
				reef = new Reef($('.form-wrapper'));
			}
		});
	};
	
	var builder;
	var fn_initBuilder = function() {
		builder = new ReefBuilder('.builderWrapper', {
			success : function(response) {
				$('#definition').val(response.definition);
				
				fn_loadForm();
			}
		});
	};
	fn_initBuilder();
	
	$('#submission_form').on('submit', function(evt) {
		evt.preventDefault();
		
		if(!reef.validate()) {
			return;
		}
		
		var $form = $(this);
		
		$.ajax({
			url: 'submission.php',
			method: 'post',
			data: $form.serialize(),
			success: function(response) {
				$('#submission').html(response);
			}
		});
	});
	
	$('#panel_submit_builder').on('click', function() {
		builder.submit();
	});
	
	$('#panel_submit_definition').on('click', function() {
		$.ajax({
			url: 'index.php',
			method: 'post',
			data: {'definition' : $('#definition').val()},
			dataType: 'JSON',
			success: function(response) {
				if(typeof response.errors !== 'undefined') {
					alert(response.errors.join("\n"));
					return;
				}
				
				$('#definition').val(response.definition);
				
				$.ajax({
					url: 'index.php',
					method: 'get',
					data: {'builder_only' : 1},
					success: function(response) {
						$('.builderWrapper').html(response);
						fn_initBuilder();
					}
				});
				
				fn_loadForm();
			}
		});
	});
	
	$('#panel_submit_form').on('click', function() {
		$('#submission_form').trigger('submit');
	});
});
</script>
<style>
.gutter {
	background-color: #eee;
}

.gutter-horizontal {
	display: flex;
	align-items: center;
}

.gutter-horizontal::after {
	display: block;
	content: '\22EE';
	margin-left: -2pt;
	color: #888;
}

.gutter-vertical::after {
	display: block;
	content: '\2026';
	line-height: 0.5pt;
	text-align: center;
	color: #888;
}

.panel {
	height: 50%;
	display: flex;
	flex-direction: column;
}

.panel .panel-head {
	background-color: #c7b3ce;
	padding: 0 0.5rem;
	font-size: 90%;
}

.panel-head .panel-title {
	color: #5f5e5e;
}

.panel .panel-content {
	flex: 1;
}

.panel-head .panel-submit {
	background-color: #a9a;
	border: 0;
}
</style>
</head>
<body>
<div style="height: 100vh; display: flex;">
	<div id="sandbox-left" style="height: 100%;">
		<div id="sandbox-top-left" class="panel">
			<div class="panel-head">
				<span class="panel-title">Builder</span>
				<button type="button" class="panel-submit float-right" id="panel_submit_builder">Save &raquo;</button>
			</div>
			<div class="builderWrapper" style="height: 100%; overflow: auto;">
				<?php echo($s_html); ?>
			</div>
		</div>
		<div id="sandbox-bottom-left" class="panel">
			<div class="panel-head">
				<span class="panel-title">Form</span>
				<button type="button" class="panel-submit float-right" id="panel_submit_form">Submit &raquo;</button>
			</div>
			<form action="submission.php" method="post" id="submission_form">
				<input type="hidden" name="submission_id" value="-1" />
				<div class="form-wrapper py-3"></div>
				<input type="submit" name="submit" value="submit" class="btn btn-primary mx-3" />
			</form>
		</div>
	</div>
	<div id="sandbox-right" style="height: 100%;">
		<div id="sandbox-top-right" class="panel">
			<div class="panel-head">
				<span class="panel-title float-right">Definition</span>
				<button type="button" class="panel-submit" id="panel_submit_definition">&laquo; Save</button>
			</div>
			<textarea id="definition" style="width: 100%; height: 100%; font-family: courier; font-size: 10pt;"></textarea>
		</div>
		<div id="sandbox-bottom-right" class="panel">
			<div class="panel-head">
				<span class="panel-title float-right">Submission</span>
			</div>
			<div id="submission"></div>
		</div>
	</div>
</div>
</body>
</html>