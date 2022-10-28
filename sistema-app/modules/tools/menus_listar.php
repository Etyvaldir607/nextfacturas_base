<?php

// Obtiene los menus
$menus = $db->from('sys_menus')->order_by('menu', 'asc')->fetch();

// Ordena el modelo
$menus = ordenar_menu($menus);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Menús</strong>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para agregar nuevos menús hacer clic en el siguiente botón:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/<?= tools; ?>/menus_crear" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i><span> Nuevo</span></a>
		</div>
	</div>
	<hr>
	<?php if (isset($_SESSION[temporary])) { ?>
	<?php unset($_SESSION[temporary]); ?>
	<div class="alert alert-success">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong>Eliminación satisfactoria!</strong>
		<p>El menú fue eliminado correctamente.</p>
	</div>
	<?php } ?>
	<?php if ($menus) { ?>
	<table id="table" class="table table-bordered table-condensed table-striped table-hover">
		<thead>
			<tr class="active">
				<th class="text-nowrap">Menú</th>
				<th class="text-nowrap">Ícono</th>
				<th class="text-nowrap">Ruta</th>
				<th class="text-nowrap">Opciones</th>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Menú</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Ícono</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Ruta</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($menus as $menu) { ?>
			<tr class="treegrid-<?= $menu['id_menu']; ?> <?= ($menu['antecesor_id'] == 0) ? '' : 'treegrid-parent-' . $menu['antecesor_id']; ?>">
				<td class="text-nowrap"><?= escape($menu['menu']); ?></td>
				<td class="text-nowrap">
					<i class="glyphicon glyphicon-<?= $menu['icono']; ?>"></i>
					<span><?= escape($menu['icono']); ?></span>
				</td class="text-nowrap">
				<td class="text-nowrap"><?= escape($menu['ruta']); ?></td>
				<td class="text-nowrap">
					<a href="?/<?= tools; ?>/menus_ver/<?= $menu['id_menu']; ?>" title="Ver menú"><i class="glyphicon glyphicon-search"></i></a>
					<a href="?/<?= tools; ?>/menus_editar/<?= $menu['id_menu']; ?>" title="Editar menú"><i class="glyphicon glyphicon-edit"></i></a>
					<a href="?/<?= tools; ?>/menus_eliminar/<?= $menu['id_menu']; ?>" title="Eliminar menú" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i></a>
				</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen menús registrados en la base de datos, para crear nuevos menús hacer clic en el botón nuevo o presionar las teclas <kbd>alt + n</kbd>.</p>
	</div>
	<?php } ?>
</div>
<script src="<?= js; ?>/jquery.treegrid.min.js"></script>
<script src="<?= js; ?>/treegrid.bootstrap3.min.js"></script>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
<script>
$(function () {
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'n':
					e.preventDefault();
					window.location = '?/<?= tools; ?>/menus_crear';
				break;
			}
		}
	});
	
	<?php if ($menus) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar el menú?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	
	$('#table').treegrid().DataFilter({
		filter: false,
		name: 'menus',
		values: {
			sort: false,
			stateSave: true
		}
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>