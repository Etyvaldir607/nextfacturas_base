<?php

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Obtiene la ultima dosificacion
$dosificacion = $db->from('inv_dosificaciones')->where('fecha_registro <=', date('Y-m-d'))->where('fecha_limite >=', date('Y-m-d'))->where('activo', 'S')->fetch_first();

// Obtiene la fecha limite de expiracion
$fecha_limite = $dosificacion['fecha_limite'];

// Verifica si hay alguna dosificacion
if ($dosificacion) {
	$fecha_anterior = $fecha_limite;
} else {
	$fecha_anterior = date('Y-m-d');
}

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_listar = in_array('listar', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Crear dosificación</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para regresar al listado de dosificaciones hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/dosificaciones/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span> Listar</span></a>
		</div>
	</div>
	<hr>
	<?php } ?>
	<div class="alert alert-info">
		<strong>Advertencia!</strong>
		<p>Para el registro de una nueva dosificación debe tener en cuenta las siguientes observaciones:</p>
		<ul>
			<li>La fecha límite de emisión del anterior periodo de facturación fue <strong class="text-primary"><?= date_decode($fecha_anterior, $_institution['formato']); ?></strong>.</li>
			<li>Verifique bien los datos de su certificado de dosificación antes de registrarlo al sistema.</li>
		</ul>
	</div>
	<div class="row">
		<div class="col-sm-9 col-sm-offset-1">
			<form method="post" action="?/dosificaciones/guardar" class="form-horizontal">
				<div class="form-group">
					<label for="nro_tramite" class="col-md-4 control-label">Número de trámite:</label>
					<div class="col-md-8">
						<input type="hidden" value="0" name="id_dosificacion" data-validation="required">
						<input type="text" value="" name="nro_tramite" id="nro_tramite" class="form-control" data-validation="required number length" data-validation-length="max50">
					</div>
				</div>
				<div class="form-group">
					<label for="nro_autorizacion_confirmation" class="col-md-4 control-label">Número de autorización:</label>
					<div class="col-md-8">
						<input type="text" value="" name="nro_autorizacion_confirmation" id="nro_autorizacion_confirmation" class="form-control" data-validation="required number length" data-validation-length="max50">
					</div>
				</div>
				<div class="form-group">
					<label for="nro_autorizacion" class="col-md-4 control-label">Repita número de autorización:</label>
					<div class="col-md-8">
						<input type="text" value="" name="nro_autorizacion" id="nro_autorizacion" class="form-control" data-validation="required number length confirmation" data-validation-length="max50">
					</div>
				</div>
				<div class="form-group">
					<label for="llave_dosificacion_confirmation" class="col-md-4 control-label">Llave de dosificación:</label>
					<div class="col-md-8">
						<input type="text" value="" name="llave_dosificacion_confirmation" id="llave_dosificacion_confirmation" class="form-control" data-validation="required length" data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="llave_dosificacion" class="col-md-4 control-label">Repita llave de dosificación:</label>
					<div class="col-md-8">
						<input type="text" value="" name="llave_dosificacion" id="llave_dosificacion" class="form-control" data-validation="required length confirmation" data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="fecha_limite_confirmation" class="col-md-4 control-label">Fecha límite de emisión:</label>
					<div class="col-md-8">
						<input type="text" value="" name="fecha_limite_confirmation" id="fecha_limite_confirmation" class="form-control" data-validation="required date" data-validation-format="<?= $formato_textual; ?>">
					</div>
				</div>
				<div class="form-group">
					<label for="fecha_limite" class="col-md-4 control-label">Repita fecha límite de emisión:</label>
					<div class="col-md-8">
						<input type="text" value="" name="fecha_limite" id="fecha_limite" class="form-control" data-validation="required date confirmation" data-validation-format="<?= $formato_textual; ?>">
					</div>
				</div>
				<div class="form-group">
					<label for="leyenda" class="col-md-4 control-label">Leyenda de la factura:</label>
					<div class="col-md-8">
						<input type="text" value="" name="leyenda" id="leyenda" class="form-control" data-validation="required letternumber" data-validation-allowing="-/.,:;& ">
					</div>
				</div>
				<div class="form-group">
					<label for="observacion" class="col-md-4 control-label">Observación:</label>
					<div class="col-md-8">
						<textarea name="observacion" id="observacion" class="form-control" data-validation="letternumber" data-validation-allowing="-+/.,:;@#&'()_\n " data-validation-optional="true"></textarea>
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
});
</script>
<?php require_once show_template('footer-advanced'); ?>