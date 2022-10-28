<?php

// Obtiene el id_rol
$id_rol = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el rol
$rol = $db->from('sys_roles')->where('id_rol', $id_rol)->fetch_first();

// Verifica si existe el rol
if (!$rol) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los menus
$menus = $db->select('m.*, p.rol_id, p.archivos')->from('sys_menus m')->join('sys_permisos p', 'm.id_menu = p.menu_id and p.rol_id = ' . $id_rol, 'left')->order_by('m.orden', 'asc')->fetch();

// Ordena el modelo
$menus = ordenar_menu($menus);

// Obtiene los roles
$roles = $db->from('sys_roles')->order_by('rol', 'asc')->fetch();

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Asignar permisos</strong>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-9 hidden-xs">
			<div class="text-label">Para regresar al listado de los roles hacer clic en la siguiente lista desplegable: </div>
		</div>
		<div class="col-xs-12 col-sm-3 text-right">
			<select id="roles" class="form-control form-inline">
				<option value="?/permisos/listar">Listado</option>
				<?php foreach ($roles as $elemento) { ?>
					<?php if ($elemento['id_rol'] == $id_rol) { ?>
					<option value="?/permisos/asignar/<?= $elemento['id_rol']; ?>" selected><?= escape($elemento['rol']); ?></option>
					<?php } else { ?>
					<option value="?/permisos/asignar/<?= $elemento['id_rol']; ?>"><?= escape($elemento['rol']); ?></option>
					<?php } ?>
				<?php } ?>
			</select>
		</div>
	</div>
	<hr>
	<?php if ($menus) { ?>
	<form method="post" action="?/permisos/guardar" class="form-horizontal">
		<input type="hidden" value="<?= $id_rol; ?>" name="id_rol">
		<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
			<thead>
				<tr class="active">
					<th>Menú</th>
					<th>Estado</th>
					<th>Permisos</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($menus as $menu) { ?>
				<tr class="treegrid-<?= $menu['id_menu']; ?> <?= ($menu['antecesor_id'] == 0) ? '' : 'treegrid-parent-' . $menu['antecesor_id']; ?>">
					<td class="text-nowrap text-middle">
						<i class="glyphicon glyphicon-<?= $menu['icono']; ?>"></i>
						<span><?= escape($menu['menu']); ?></span>
					</td>
					<td class="text-middle">
						<?php if ($menu['rol_id'] != null) { ?>
						<input type="checkbox" value="<?= $menu['id_menu']; ?>" name="estados[<?= $menu['id_menu']; ?>]" checked data-indice="<?= $menu['id_menu']; ?>" data-antecesor="<?= $menu['antecesor_id']; ?>">
						<?php } else { ?>
						<input type="checkbox" value="<?= $menu['id_menu']; ?>" name="estados[<?= $menu['id_menu']; ?>]" data-indice="<?= $menu['id_menu']; ?>" data-antecesor="<?= $menu['antecesor_id']; ?>">
						<?php } ?>
					</td>
					<td>
						<?php $files = (get_files(modules . '/' . $menu['modulo']) == array()) ? '' : implode(', ', get_files(modules . '/' . $menu['modulo'])); ?>
						<?php if ($menu['antecesor'] == 1 || $files == '') { ?>
						<input type="text" value="" name="archivos[<?= $menu['id_menu']; ?>]" class="form-control" autocomplete="off" tabindex="-1" readonly>
						<?php } else { ?>
						<input type="text" value="<?= $menu['archivos']; ?>" name="archivos[<?= $menu['id_menu']; ?>]" class="form-control paste" autocomplete="off">
						<small><span class="text-primary">[<?= escape($menu['modulo']); ?>]</span> disponibles &mdash; <span class="text-success"><?= $files; ?></span></small>
						<?php } ?>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<div class="form-group">
			<div class="col-xs-12 text-right">
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
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen menús registrados en la base de datos, es imposible asignar permisos a menús inexistentes.</p>
	</div>
	<?php } ?>
</div>
<script src="<?= js; ?>/jquery.treegrid.min.js"></script>
<script src="<?= js; ?>/treegrid.bootstrap3.min.js"></script>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script>
$(function () {
	<?php if ($menus) { ?>
	$('#table').treegrid();

	$('#table').dataTable({
		sort: false,
		paging: false,
		info: false,
		stateSave: true
	});

	$(':checkbox').on('change', function () {
		$this = $(this);
		$('[data-antecesor=' + $this.val() + ']').prop('checked', $this.is(':checked'));
	});

	$('.paste').on('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'r':
					e.preventDefault();
					var $this = $(this);
					var text = $this.next().find('span:last').text();
					text = text.replace(new RegExp('\\s+', 'g'), '');
					$this.val(text);
				break;
			}
		}
	});
	<?php } ?>

	$('#roles').on('change', function () {
		window.location = $(this).val();
	});
});
</script>
<?php require_once show_template('footer-advanced'); ?>