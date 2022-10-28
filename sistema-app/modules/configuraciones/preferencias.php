<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<b>Ajustes sobre la fecha</b>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para editar la información hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/configuraciones/preferencias_editar" class="btn btn-primary">
				<span class="glyphicon glyphicon-edit"></span>
				<span>Modificar</span>
			</a>
		</div>
	</div>
	<hr>
	<div class="well">
		<div class="table-display">
			<div class="tbody">
				<div class="tr">
					<div class="th text-nowrap">Formato para las fechas:</div>
					<div class="td text-ellipsis"><?= escape(get_date_textual($_institution['formato'])); ?></div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
$(function () {
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'u':
					e.preventDefault();
					window.location = '?/configuraciones/preferencias_editar';
				break;
			}
		}
	});
});
</script>
<?php require_once show_template('footer-advanced'); ?>