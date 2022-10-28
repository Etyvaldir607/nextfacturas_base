<?php

// Obtiene el id_dosificacion
$id_dosificacion = (sizeof($params) > 0) ? $params[0] : 0;

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
$permiso_editar = in_array('editar', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);
$permiso_listar = in_array('listar', $permisos);

// Define la fecha de hoy
$hoy = date('Y-m-d');

// Obtiene la vigencia en dias
$vigencia = date_diff(date_create($dosificacion['fecha_registro']), date_create($dosificacion['fecha_limite']));
$vigencia = intval($vigencia->format('%a'));
$estado = ($hoy < $dosificacion['fecha_registro']) ? 'En espera' : (($hoy > $dosificacion['fecha_limite']) ? 'Sin vigencia' : 'En uso');

// Define las alertas 
$alertas = array(
	'Sin vigencia' => 'text-danger',
	'En uso' => 'text-success',
	'En espera' => 'text-primary'
);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Ver dosificación</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_crear || $permiso_editar || $permiso_eliminar || $permiso_imprimir || $permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<?php if ($permiso_crear) { ?>
			<a href="?/dosificaciones/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<?php } ?>
			<?php if ($permiso_editar) { ?>
			<a href="?/dosificaciones/editar/<?= $dosificacion['id_dosificacion']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs hidden-sm"> Editar</span></a>
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
			<a href="?/dosificaciones/eliminar/<?= $dosificacion['id_dosificacion']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<?php } ?>
			<?php if ($permiso_imprimir) { ?>
			<a href="?/dosificaciones/imprimir/<?= $dosificacion['id_dosificacion']; ?>" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs hidden-sm"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/dosificaciones/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs hidden-sm <?= ($permiso_imprimir) ? 'hidden-md' : ''; ?>"> Listado</span></a>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<div class="form-horizontal">
				<div class="form-group">
					<label class="col-md-3 control-label">#:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($dosificacion['id_dosificacion']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Número de trámite:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($dosificacion['nro_tramite']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Número de autorización:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($dosificacion['nro_autorizacion']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Llave de dosificación:</label>
					<div class="col-md-9">
						<p class="form-control-static"><code><?= base64_decode($dosificacion['llave_dosificacion']); ?></code></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Fecha límite de emisión:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= date_decode(escape($dosificacion['fecha_limite']), $_institution['formato']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Vigencia en días:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($vigencia + 1); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Número de facturas emitidas:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($dosificacion['nro_facturas']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Estado:</label>
					<div class="col-md-9">
						<p class="form-control-static"><b class="<?= $alertas[$estado]; ?>"><?= escape($estado); ?></b></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Leyenda de la factura:</label>
					<div class="col-md-9">
						<p class="form-control-static">Ley Nº 453: "<?= escape($dosificacion['leyenda']); ?>"</p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Observación:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($dosificacion['observacion']); ?></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php if ($permiso_eliminar) { ?>
<script>
$(function () {
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar la dosificación?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
});
</script>
<?php } ?>
<?php require_once show_template('footer-advanced'); ?>