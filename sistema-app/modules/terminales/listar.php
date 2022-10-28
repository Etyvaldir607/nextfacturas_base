<?php

// Obtiene las terminales
$terminales = $db->select('z.*')->from('inv_terminales z')->order_by('z.id_terminal')->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);
$permiso_descargar = in_array('descargar', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Terminales</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if (($permiso_crear || $permiso_imprimir) && ($permiso_crear || $terminales)) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para agregar nuevas terminales hacer clic en el siguiente botón: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_imprimir) { ?>
			<a href="?/terminales/imprimir" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<a href="?/terminales/crear" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i><span> Nuevo</span></a>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<?php if (isset($_SESSION[temporary])) { ?>
	<div class="alert alert-<?= $_SESSION[temporary]['alert']; ?>">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong><?= $_SESSION[temporary]['title']; ?></strong>
		<p><?= $_SESSION[temporary]['message']; ?></p>
	</div>
	<?php unset($_SESSION[temporary]); ?>
	<?php } ?>
	<?php if ($terminales) { ?>
	<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
				<th class="text-nowrap">Terminal</th>
				<th class="text-nowrap">Impresora</th>
				<th class="text-nowrap">Descripción</th>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar || $permiso_descargar) { ?>
				<th class="text-nowrap">Opciones</th>
				<?php } ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Terminal</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Impresora</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Descripción</th>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar || $permiso_descargar) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($terminales as $nro => $terminal) { ?>
			<tr>
				<th class="text-nowrap"><?= $nro + 1; ?></th>
				<td class="text-nowrap"><?= escape($terminal['terminal']); ?></td>
				<td class="text-nowrap"><?= escape($terminal['impresora']); ?></td>
				<td class="text-nowrap"><?= escape($terminal['descripcion']); ?></td>
				<?php if ($permiso_ver || $permiso_editar || $permiso_eliminar || $permiso_descargar) { ?>
				<td class="text-nowrap">
					<?php if ($permiso_ver) { ?>
					<a href="?/terminales/ver/<?= $terminal['id_terminal']; ?>" data-toggle="tooltip" data-title="Ver terminal"><i class="glyphicon glyphicon-search"></i></a>
					<?php } ?>
					<?php if ($permiso_editar) { ?>
					<a href="?/terminales/editar/<?= $terminal['id_terminal']; ?>" data-toggle="tooltip" data-title="Editar terminal"><i class="glyphicon glyphicon-edit"></i></a>
					<?php } ?>
					<?php if ($permiso_eliminar) { ?>
					<a href="?/terminales/eliminar/<?= $terminal['id_terminal']; ?>" data-toggle="tooltip" data-title="Eliminar terminal" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i></a>
					<?php } ?>
					<?php if ($permiso_descargar) { ?>
					<a href="?/terminales/descargar/<?= $terminal['id_terminal']; ?>" data-toggle="tooltip" data-title="Descargar archivo remoto"><i class="glyphicon glyphicon-download-alt"></i></a>
					<?php } ?>
				</td>
				<?php } ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen terminales registradas en la base de datos, para crear nuevas terminales hacer clic en el botón nuevo o presionar las teclas <kbd>alt + n</kbd>.</p>
	</div>
	<?php } ?>
</div>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
<script>
$(function () {
	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar la terminal?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>
	
	<?php if ($permiso_crear) { ?>
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'n':
					e.preventDefault();
					window.location = '?/terminales/crear';
				break;
			}
		}
	});
	<?php } ?>
	
	<?php if ($terminales) { ?>
	var table = $('#table').DataFilter({
		filter: false,
		name: 'terminales',
		reports: 'excel|word|pdf|html'
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>