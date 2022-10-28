<?php

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_listar = in_array('listar', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Crear moneda</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para regresar al listado de monedas hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/monedas/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Listado</span></a>
		</div>
	</div>
	<hr>
	<?php } ?>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="post" action="?/monedas/guardar" class="form-horizontal">
				<div class="form-group">
					<label for="moneda" class="col-md-3 control-label">Moneda:</label>
					<div class="col-md-9">
						<input type="hidden" value="0" name="id_moneda" data-validation="required">
						<input type="text" value="" name="moneda" id="moneda" class="form-control" autocomplete="off" data-validation="required letter length" data-validation-allowing="() " data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="sigla" class="col-md-3 control-label">Sigla:</label>
					<div class="col-md-9">
						<input type="text" value="" name="sigla" id="sigla" class="form-control" autocomplete="off" data-validation="required letter length" data-validation-allowing=". " data-validation-length="max10">
					</div>
				</div>
				<div class="form-group">
					<label for="oficial" class="col-md-3 control-label">Oficial:</label>
					<div class="col-md-9">
						<div class="radio">
							<label>
								<input type="radio" name="oficial" value="N" checked>
								<span>No</span>
							</label>
						</div>
						<div class="radio">
							<label>
								<input type="radio" name="oficial" value="S">
								<span>Si</span>
							</label>
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-9 col-md-offset-3">
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
		modules: 'basic'
	});
	
	$('.form-control:first').select();
});
</script>
<?php require_once show_template('footer-advanced'); ?>