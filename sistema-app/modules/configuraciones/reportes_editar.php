<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<b>Ajustes sobre los reportes</b>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para visualizar los datos hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/configuraciones/reportes" class="btn btn-primary">
				<span class="glyphicon glyphicon-search"></span>
				<span>Mostrar</span>
			</a>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="post" action="?/configuraciones/reportes_guardar" enctype="multipart/form-data" class="form-horizontal">
				<div class="form-group">
					<label for="imagen_encabezado" class="col-md-4 control-label">Imagen para el encabezado:</label>
					<div class="col-md-8">
						<input type="file" name="imagen_encabezado" id="imagen_encabezado" class="form-control" data-validation="mime size" data-validation-allowing="jpg,png" data-validation-max-size="1M">
					</div>
				</div>
				<div class="form-group">
					<label for="pie_pagina" class="col-md-4 control-label">Texto para el pie de página:</label>
					<div class="col-md-8">
						<input type="text" value="<?= $_institution['pie_pagina']; ?>" name="pie_pagina" id="pie_pagina" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="+-/.,:;@#&() " data-validation-length="10-200">
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-8 col-md-offset-4">
						<button type="submit" class="btn btn-primary">
							<span class="glyphicon glyphicon-floppy-disk"></span>
							<span>Guardar</span>
						</button>
						<button type="reset" class="btn btn-default">
							<span class="glyphicon glyphicon-refresh"></span>
							<span>Restablecer</span>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script>
$(function () {
	$.validate({
		modules: 'basic,file'
	});
	
	$('.form-control:first').select();
});
</script>
<?php require_once show_template('footer-advanced'); ?>