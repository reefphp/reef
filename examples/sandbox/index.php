<?php

require_once('./common.php');

$Form = $Reef->newTempForm($_SESSION['sandbox']['definition']);

$Builder = $Reef->getBuilder();
$Builder->setSettings([
	'submit_action' => 'index.php',
	'definition_form_creator' => function($Creator) {
		$Creator
			->getFieldByName('storage_name')
				->delete();
	},
]);

if(isset($_POST['builder_data'])) {
	$Builder->processBuilderData_write($Form, $_POST['builder_data'], function(&$a_return) use($Form) {
		if($a_return['result']) {
			$_SESSION['sandbox']['definition'] = $Form->generateDefinition();
			
			$a_return['definition'] = \Symfony\Component\Yaml\Yaml::dump($_SESSION['sandbox']['definition'], 5);
		}
	});
}

if(isset($_POST['definition'])) {
	try {
		$Form->updateDefinition(\Symfony\Component\Yaml\Yaml::parse($_POST['definition']));
		
		$_SESSION['sandbox']['definition'] = $Form->generateDefinition();
		
		$a_return['definition'] = \Symfony\Component\Yaml\Yaml::dump($_SESSION['sandbox']['definition'], 5);
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

if(isset($_GET['builder_only'])) {
	echo($s_html);
	die();
}

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
	<script src="https://unpkg.com/split.js/split.min.js"></script>
	
	<title>Form sandbox</title>
<script>
var reef;
var builder;

function recursive_table(data) {
	var name, value, $table, $tr;
	
	$table = $('<table class="table">');
	
	for(name in data) {
		value = data[name];
		
		$tr = $('<tr>').append($('<th>').text(name));
		
		if(typeof(value) === 'object') {
			$tr.append($('<td>').html(recursive_table(value)));
		}
		else {
			if(value == '') {
				$tr.append($('<td>').html('<span class="text-muted font-italic">Empty</span>'));
			}
			else {
				$tr.append($('<td>').text(value));
			}
		}
		
		$table.append($tr);
	}
	
	return $table;
}

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
			data: {
				locale : $('#locale_setter_form').val()
			},
			success: function(response) {
				$('.form-wrapper').html(response);
				
				reef = new Reef($('.form-wrapper'), {
					submit_url : 'submission.php',
					submit_form : $('#submission_form'),
					submit_success : function(submit_response) {
						$('#submission').html(recursive_table(submit_response.data));
					}
				});
			}
		});
	};
	
	var fn_initBuilder = function() {
		builder = new ReefBuilder('.builderWrapper', {
			success : function(response) {
				$('#definition').val(response.definition);
				
				fn_loadForm();
			}
		});
	};
	fn_initBuilder();
	
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
					builder.addErrors(response.errors);
					return;
				}
				
				$('#definition').val(response.definition);
				
				$.ajax({
					url: 'index.php',
					method: 'get',
					data: {
						builder_only : 1,
						locale : $('#locale_setter_form').val()
					},
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
	
	$('#locale_setter_builder').on('change', function() {
		builder.submit(function(response) {
			location.href = 'index.php?locale='+$('#locale_setter_builder').val();
		});
	});
	
	$('#locale_setter_form').on('change', function() {
		fn_loadForm();
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
	overflow: auto;
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
				
				<select id="locale_setter_builder" style="font-size: 7pt; vertical-align: top;">
				<?php foreach($a_locales as $s_locale) { ?>
					<option value="<?php echo($s_locale); ?>"><?php echo($s_locale); ?></option>
				<?php } ?>
				</select>
				
				<button type="button" class="panel-submit float-right" id="panel_submit_builder">Save &raquo;</button>
			</div>
			<div class="builderWrapper" style="height: 100%; overflow: auto;">
				<?php echo($s_html); ?>
			</div>
		</div>
		<div id="sandbox-bottom-left" class="panel">
			<div class="panel-head">
				<span class="panel-title">Form</span>
				
				<select id="locale_setter_form" style="font-size: 7pt; vertical-align: top;">
				<?php foreach($a_locales as $s_locale) { ?>
					<option value="<?php echo($s_locale); ?>"><?php echo($s_locale); ?></option>
				<?php } ?>
				</select>
				
				<button type="button" class="panel-submit float-right" id="panel_submit_form">Submit &raquo;</button>
			</div>
			<div class="form-wrapper py-3"></div>
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
