<?php

// Obtiene los roles
$roles = $db->select('r.*')->from('sys_roles r')->fetch();

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Permisos</strong>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-xs-12">
			<div class="text-label">Para asignar permisos a los roles de la lista hacer clic en el enlace <i class="glyphicon glyphicon-lock"></i>.</div>
		</div>
	</div>
	<hr>
	<?php if ($roles) { ?>
	<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
		<thead>
			<tr class="active">
				<th>#</th>
				<th>Rol</th>
				<th>Opciones</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($roles as $rol) { ?>
			<tr>
				<td><?= escape($rol['id_rol']); ?></td>
				<td><?= escape($rol['rol']); ?></td>
				<td>
					<a href="?/permisos/asignar/<?= $rol['id_rol']; ?>" data-toggle="tooltip" data-title="Asignar permisos"><i class="glyphicon glyphicon-lock"></i></a>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen roles registrados en la base de datos, por o cual es imposible asignar los permisos correspondientes.</p>
	</div>
	<?php } ?>
</div>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<?php if ($roles) { ?>
<script>
$(function () {
	$('#table').dataTable({
		paging: false,
		info: false
	});
});
</script>
<?php } ?>
<?php require_once show_template('footer-advanced'); ?>