<?php

// Obtiene el id_terminal
$id_terminal = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la terminal
$terminal = $db->select('z.*')
->from('inv_terminales z')
->where('z.id_terminal', $id_terminal)
->fetch_first();

// Verifica si existe la terminal
if (!$terminal) {
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
		<strong>Ver terminal</strong>
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
			<a href="?/terminales/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<?php } ?>
			<?php if ($permiso_editar) { ?>
			<a href="?/terminales/editar/<?= $terminal['id_terminal']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs hidden-sm"> Editar</span></a>
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
			<a href="?/terminales/eliminar/<?= $terminal['id_terminal']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<?php } ?>
			<?php if ($permiso_imprimir) { ?>
			<a href="?/terminales/imprimir/<?= $terminal['id_terminal']; ?>" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs hidden-sm"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/terminales/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs hidden-sm <?= ($permiso_imprimir) ? 'hidden-md' : ''; ?>"> Listado</span></a>
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
						<p class="form-control-static"><?= escape($terminal['id_terminal']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Terminal:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($terminal['terminal']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Impresora:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($terminal['impresora']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Descripción:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($terminal['descripcion']); ?></p>
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
		bootbox.confirm('Está seguro que desea eliminar la terminal?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
});
</script>
<?php } ?>
<?php require_once show_template('footer-advanced'); ?>