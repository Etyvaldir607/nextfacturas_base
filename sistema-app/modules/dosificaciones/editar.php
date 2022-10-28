<?php

// Obtiene el id_dosificacion
$id_dosificacion = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Obtiene la dosificación
$dosificacion = $db->from('inv_dosificaciones')->where('id_dosificacion', $id_dosificacion)->fetch_first();

// Verifica si existe la dosificación
if (!$dosificacion) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_listar = in_array('listar', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Editar dosificación</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_crear || $permiso_ver || $permiso_eliminar || $permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<?php if ($permiso_crear) { ?>
			<a href="?/dosificaciones/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<?php } ?>
			<?php if ($permiso_ver) { ?>
			<a href="?/dosificaciones/ver/<?= $dosificacion['id_dosificacion']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-search"></i><span class="hidden-xs hidden-sm"> Ver</span></a>
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
			<a href="?/dosificaciones/eliminar/<?= $dosificacion['id_dosificacion']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/dosificaciones/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs"> Listado</span></a>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<div class="row">
		<div class="col-sm-9 col-sm-offset-1">
			<form method="post" action="?/dosificaciones/guardar" class="form-horizontal">
				<div class="form-group">
					<label for="nro_tramite" class="col-md-4 control-label">Número de trámite:</label>
					<div class="col-md-8">
						<input type="hidden" value="<?= $dosificacion['id_dosificacion']; ?>" name="id_dosificacion" data-validation="required">
						<input type="text" value="<?= $dosificacion['nro_autorizacion']; ?>" name="nro_tramite" id="nro_tramite" class="form-control" data-validation="required number length" data-validation-length="max50">
					</div>
				</div>
				<div class="form-group">
					<label for="nro_autorizacion_confirmation" class="col-md-4 control-label">Número de autorización:</label>
					<div class="col-md-8">
						<input type="text" value="<?= $dosificacion['nro_autorizacion']; ?>" name="nro_autorizacion_confirmation" id="nro_autorizacion_confirmation" class="form-control" data-validation="required number length" data-validation-length="max50">
					</div>
				</div>
				<div class="form-group">
					<label for="nro_autorizacion" class="col-md-4 control-label">Repita número de autorización:</label>
					<div class="col-md-8">
						<input type="text" value="<?= $dosificacion['nro_autorizacion']; ?>" name="nro_autorizacion" id="nro_autorizacion" class="form-control" data-validation="required number length confirmation" data-validation-length="max50">
					</div>
				</div>
				<div class="form-group">
					<label for="llave_dosificacion_confirmation" class="col-md-4 control-label">Llave de dosificación:</label>
					<div class="col-md-8">
						<input type="text" value="<?= base64_decode($dosificacion['llave_dosificacion']); ?>" name="llave_dosificacion_confirmation" id="llave_dosificacion_confirmation" class="form-control" data-validation="required length" data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="llave_dosificacion" class="col-md-4 control-label">Repita llave de dosificación:</label>
					<div class="col-md-8">
						<input type="text" value="<?= base64_decode($dosificacion['llave_dosificacion']); ?>" name="llave_dosificacion" id="llave_dosificacion" class="form-control" data-validation="required length confirmation" data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="fecha_limite_confirmation" class="col-md-4 control-label">Fecha límite de emisión:</label>
					<div class="col-md-8">
						<input type="text" value="<?= date_decode(escape($dosificacion['fecha_limite']), $_institution['formato']); ?>" name="fecha_limite_confirmation" id="fecha_limite_confirmation" class="form-control" data-validation="required date" data-validation-format="<?= $formato_textual; ?>">
					</div>
				</div>
				<div class="form-group">
					<label for="fecha_limite" class="col-md-4 control-label">Repita fecha límite de emisión:</label>
					<div class="col-md-8">
						<input type="text" value="<?= date_decode(escape($dosificacion['fecha_limite']), $_institution['formato']); ?>" name="fecha_limite" id="fecha_limite" class="form-control" data-validation="required date confirmation" data-validation-format="<?= $formato_textual; ?>">
					</div>
				</div>
				<div class="form-group">
					<label for="leyenda" class="col-md-4 control-label">Leyenda de la factura:</label>
					<div class="col-md-8">
						<input type="text" value="<?= escape($dosificacion['leyenda']); ?>" name="leyenda" id="leyenda" class="form-control" data-validation="required letternumber" data-validation-allowing="-/.,:;& ">
					</div>
				</div>
				<div class="form-group">
					<label for="observacion" class="col-md-4 control-label">Observación:</label>
					<div class="col-md-8">
						<textarea name="observacion" id="observacion" class="form-control" data-validation="letternumber" data-validation-allowing="-+/.,:;@#&'()_\n " data-validation-optional="true"><?= escape($dosificacion['observacion']); ?></textarea>
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
<script src="<?= js; ?>/jquery.maskedinput.min.js"></script>
<script>
$(function () {
	$.validate({
		modules: 'basic,security'
	});

	$('#fecha_limite_confirmation, #fecha_limite').mask('<?= $formato_numeral; ?>');
	
	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar la dosificación?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>