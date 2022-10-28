<?php

// Obtiene el id_moneda
$id_moneda = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la moneda
$moneda = $db->select('z.*')
->from('inv_monedas z')
->where('z.id_moneda', $id_moneda)
->fetch_first();

// Verifica si existe la moneda
if (!$moneda) {
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
		<strong>Ver moneda</strong>
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
			<a href="?/monedas/crear" class="btn btn-success"><i class="glyphicon glyphicon-plus"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<?php } ?>
			<?php if ($permiso_editar) { ?>
			<a href="?/monedas/editar/<?= $moneda['id_moneda']; ?>" class="btn btn-warning"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs hidden-sm"> Editar</span></a>
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
			<a href="?/monedas/eliminar/<?= $moneda['id_moneda']; ?>" class="btn btn-danger" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<?php } ?>
			<?php if ($permiso_imprimir) { ?>
			<a href="?/monedas/imprimir/<?= $moneda['id_moneda']; ?>" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs hidden-sm"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/monedas/listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs hidden-sm <?= ($permiso_imprimir) ? 'hidden-md' : ''; ?>"> Listado</span></a>
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
						<p class="form-control-static"><?= escape($moneda['id_moneda']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Moneda:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($moneda['moneda']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Sigla:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= escape($moneda['sigla']); ?></p>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Oficial:</label>
					<div class="col-md-9">
						<p class="form-control-static"><?= (escape($moneda['oficial']) == 'S') ? 'Si' : 'No'; ?></p>
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
		bootbox.confirm('Está seguro que desea eliminar la moneda?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
});
</script>
<?php } ?>
<?php require_once show_template('footer-advanced'); ?>