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
		<strong>Crear terminal</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para regresar al listado de terminales hacer clic en el siguiente boton:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/terminales/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Listar</span></a>
		</div>
	</div>
	<hr>
	<?php } ?>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="post" action="?/terminales/guardar" class="form-horizontal">
				<div class="form-group">
					<label for="terminal" class="col-md-3 control-label">Terminal:</label>
					<div class="col-md-9">
						<input type="hidden" value="0" name="id_terminal" data-validation="required">
						<input type="text" value="" name="terminal" id="terminal" class="form-control" autocomplete="off" data-validation="required alphanumeric length" data-validation-allowing="-/.()_ " data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="impresora" class="col-md-3 control-label">Impresora:</label>
					<div class="col-md-9">
						<input type="text" value="" name="impresora" id="impresora" class="form-control" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-()_ " data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="descripcion" class="col-md-3 control-label">Descripcion:</label>
					<div class="col-md-9">
						<textarea name="descripcion" id="descripcion" class="form-control" autocomplete="off" data-validation="letternumber" data-validation-allowing="+-/.,:;@#()_\n " data-validation-optional="true"></textarea>
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