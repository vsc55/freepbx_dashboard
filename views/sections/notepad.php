<div class="container-fluid" id="dashboard_notepad_container">

	<div class="row">
		<div class="col-sm-12 less-padding">
<?php foreach ($data as $timestamp=>$note): ?>
			<div class="row box" id="dashboard_notepad_<?= $timestamp ?>">
				<div class="col-sm-11 text-justify">
					<strong title="<?= htmlspecialchars($note->time) ?>">
						<?= htmlspecialchars(sprintf("%s ago:", $note->ago)) ?>
					</strong>
					<?= htmlspecialchars($note->content) ?>
				</div>
				<div class="col-sm-1">
					<button data-timestamp="<?= $timestamp ?>" class="btn-link dashboard_notepad_del">
						<i class="fa fa-times-circle " title="<?= htmlspecialchars(_("Delete This")) ?>"></i>
					</button>
				</div>
			</div>
<?php endforeach; ?>
		</div>
	</div>

	<div class="row collapse" id="dashboard_notepad_new">
		<div class="col-sm-12 less-padding">
				<div class="row box">
					<div class="col-sm-10">
						<textarea name="content" id="dashboard_notepad_content" class="form-control"></textarea>
					</div>
					<div class="col-sm-2 btn-group-xs">
						<button type="button" id="dashboard_notepad_save" class="btn"><?= _("Save") ?></button>
					</div>
				</div>
		</div>
	</div>

	<div class="row">
		<div class="col-sm-12">
			<button class="btn-link" data-target="#dashboard_notepad_new" data-toggle="collapse">
				<i class="fa fa-plus-circle" title="<?= htmlspecialchars(_("Add a note")) ?>"></i>
			</button>
		</div>
	</div>

</div>

<script>
	$('.panel-collapse').on('shown.bs.collapse', function() { $('.page').packery(); });
	$('.panel-collapse').on('hidden.bs.collapse', function() { $('.page').packery(); });
	$("#dashboard_notepad_save").on("click", function() {
		var content = $("#dashboard_notepad_content").val();
		var page = $(this).closest("div.page").attr("id");
		var rawname = $(this).closest("div[data-section]").data("rawname");
		var section = $(this).closest("div[data-section]").data("section");
		var div = $("#" + page + "_" + rawname + "_" + section);
		$.post(
			"ajax.php",
			{command: "builtin_notepad_save", module: "dashboard", content: content},
			function(){
				Dashboard.updateBox(page, div);
			}
		);
	});
	$(".dashboard_notepad_del").on("click", function() {
		var id = $(this).data("timestamp");
		var page = $(this).closest("div.page").attr("id");
		var rawname = $(this).closest("div[data-section]").data("rawname");
		var section = $(this).closest("div[data-section]").data("section");
		var div = $("#" + page + "_" + rawname + "_" + section);
		$.post(
			"ajax.php",
			{command: "builtin_notepad_del", module: "dashboard", id: id},
			function(){
				Dashboard.updateBox(page, div);
			}
		)
	});
</script>