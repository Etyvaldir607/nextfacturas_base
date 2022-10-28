<?php

// Obtiene el id_empleado
$id_empleado = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el empleado
$empleado = $db->select('z.*')
->from('sys_empleados z')
->where('z.id_empleado', $id_empleado)
->fetch_first();

// Verifica si existe el empleado
if (!$empleado) {
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

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Ver empleado</strong>
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
			<a href="?/empleados/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<?php } ?>
			<?php if ($permiso_editar) { ?>
			<a href="?/empleados/editar/<?= $empleado['id_empleado']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs hidden-sm"> Editar</span></a>
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
			<a href="?/empleados/eliminar/<?= $empleado['id_empleado']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<?php } ?>
			<?php if ($permiso_imprimir) { ?>
			<a href="?/empleados/imprimir/<?= $empleado['id_empleado']; ?>" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs hidden-sm"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/empleados/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs hidden-sm <?= ($permiso_imprimir) ? 'hidden-md' : ''; ?>"> Listado</span></a>
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
						<p class="form-control-static"><?= escape($empleado['id_empleado']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Nombres:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($empleado['nombres']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Apellido paterno:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($empleado['paterno']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Apellido materno:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($empleado['materno']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Género:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($empleado['genero']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Fecha de nacimiento:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= date_decode(escape($empleado['fecha_nacimiento']), $_institution['formato']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Teléfono:</label>
					<div class="col-md-9">
						<p class="form-control-static">
						<?php $telefono = explode(',', escape($empleado['telefono'])); ?>
						<?php foreach ($telefono as $elemento) { ?>
							<span class="label label-success"><?= $elemento; ?></span>
						<?php } ?>
						</p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Empresa:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?php if($empleado['cargo']==1){echo $_institution['empresa1'];}else{echo $_institution['empresa2'];} ?></p>
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
		bootbox.confirm('Está seguro que desea eliminar el empleado?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
});
</script>
<?php } ?>
<?php require_once show_template('footer-advanced'); ?>