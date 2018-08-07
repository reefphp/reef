<?php

require_once('./common.php');

$a_formIds = $Reef->getFormIds();
if(empty($a_formIds)) {
	header("Location: ./builder.php");
	exit();
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
	
	<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
	
	<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.7.0/Sortable.min.js" integrity="sha256-+BvLlLgWJALRwV4lbCh0i4zqHhDqxR8FKUJmIl/u/vQ=" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/mustache.js/2.3.0/mustache.min.js" integrity="sha256-iaqfO5ue0VbSGcEiQn+OeXxnxAMK2+QgHXIDA5bWtGI=" crossorigin="anonymous"></script>

	<title>Form builder</title>
	<style>
	body {
		background-color: #eee;
	}
	.container {
		background-color: #fff;
		padding-bottom: 200px;
	}
	</style>
</head>
<body>
<div class="container">
	<a class="btn btn-primary my-3" href="builder.php?form_id=-1">New form</a>
	<table class="table table-sm">
	<?php
	foreach($a_formIds as $i_id) {
		?>
		<tr>
			<td>
				<a class="btn btn-link" href="builder.php?form_id=<?php echo($i_id); ?>">Edit form <?php echo($i_id); ?></a>
			</td>
			<td>
				<a class="btn btn-link text-muted" href="builder.php?form_id=<?php echo($i_id); ?>&amp;mode=delete">Delete form <?php echo($i_id); ?></a>
			</td>
			<td>
				<a class="btn btn-primary my-3" href="submission.php?form_id=<?php echo($i_id); ?>&amp;submission_id=-1">Fill in form <?php echo($i_id); ?></a>
				<a class="btn btn-primary my-3" href="builder.php?form_id=<?php echo($i_id); ?>&amp;mode=download">Download CSV</a>
				<table class="table table-sm">
				<?php
				foreach($Reef->getForm($i_id)->getSubmissionIds() as $i_submissionId) {
					?>
					<tr>
						<td>
							<a class="btn btn-link" href="submission.php?form_id=<?php echo($i_id); ?>&amp;submission_id=<?php echo($i_submissionId); ?>&amp;mode=view">View submission <?php echo($i_submissionId); ?></a>
						</td>
						<td>
							<a class="btn btn-link" href="submission.php?form_id=<?php echo($i_id); ?>&amp;submission_id=<?php echo($i_submissionId); ?>">Edit submission <?php echo($i_submissionId); ?></a>
						</td>
						<td>
							<a class="btn btn-link text-muted" href="submission.php?form_id=<?php echo($i_id); ?>&amp;submission_id=<?php echo($i_submissionId); ?>&amp;mode=delete">Delete submission <?php echo($i_submissionId); ?></a>
						</td>
					</tr>
					<?php
				}
				?>
				</table>
			</td>
		</tr>
		<?php
	}
	?>
	</table>
</div>
</body>
</html>
