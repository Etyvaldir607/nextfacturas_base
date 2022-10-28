<?php

// Obtiene las dosificaciones
$dosificaciones = $db->from('inv_dosificaciones')->order_by('fecha_registro desc, hora_registro desc')->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);
$permiso_bloquear = in_array('bloquear', $permisos);

// Define las alertas
$alertas = array(
	'Sin vigencia' => 'danger',
	'En uso' => 'success',
	'En espera' => ''
);

// Define la fecha de hoy
$hoy = date('Y-m-d');

//$dosificacion = $db->from('inv_dosificaciones')->where('fecha_registro <=', $hoy)->where('fecha_limite >=', $hoy)->where('activo', 'S')->fetch_first();

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Dosificaciones</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if (($permiso_crear || $permiso_imprimir) && ($permiso_crear || $dosificaciones)) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para agregar nuevas dosificaciones hacer clic en el siguiente botón: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_imprimir) { ?>
			<a href="?/dosificaciones/imprimir" target="_blank" class="btn btn-info"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<a href="?/dosificaciones/crear" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i><span> Nuevo</span></a>
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
	<?php if ($dosificaciones) { ?>
	<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
				<th class="text-nowrap">Fecha de registro</th>
				<th class="text-nowrap">Número de trámite</th>
				<th class="text-nowrap">Número de autorización</th>
				<th class="text-nowrap">Llave de dosificación</th>
				<th class="text-nowrap">Fecha límite de emisión</th>
				<th class="text-nowrap">Leyenda</th>
				<th class="text-nowrap">Observación</th>
				<th class="text-nowrap">Estado</th>
				<th class="text-nowrap">Días restantes</th>
				<th class="text-nowrap">Facturas</th>
				<?php if ($permiso_bloquear || $permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<th class="text-nowrap">Opciones</th>
				<?php } ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
				<th class="text-nowrap text-middle">Fecha de registro</th>
				<th class="text-nowrap text-middle">Número de trámite</th>
				<th class="text-nowrap text-middle">Número de autorización</th>
				<th class="text-nowrap text-middle" data-datafilter-visible="false">Llave de dosificación</th>
				<th class="text-nowrap text-middle">Fecha límite de emisión</th>
				<th class="text-nowrap text-middle">Leyenda</th>
				<th class="text-nowrap text-middle" data-datafilter-visible="false">Observación</th>
				<th class="text-nowrap text-middle">Estado</th>
				<th class="text-nowrap text-middle">Días restantes</th>
				<th class="text-nowrap text-middle">Facturas</th>
				<?php if ($permiso_bloquear || $permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($dosificaciones as $nro => $dosificacion) { ?>
			<?php $estado = ($hoy < $dosificacion['fecha_registro']) ? 'En espera' : (($hoy > $dosificacion['fecha_limite']) ? 'Sin vigencia' : 'En uso'); ?>
			<?php $vigencia = intval(date_diff(date_create($dosificacion['fecha_registro']), date_create($dosificacion['fecha_limite']))->format('%a')); ?>
			<?php $restante = intval(date_diff(date_create($hoy), date_create($dosificacion['fecha_limite']))->format('%a')); ?>
			<tr>
				<th><?= $nro + 1; ?></th>
				<td class="text-nowrap">
					<span><?= date_decode(escape($dosificacion['fecha_registro']), $_institution['formato']); ?></span><br>
					<span class="text-primary"><?= escape($dosificacion['hora_registro']); ?></span>
				</td>
				<td><?= escape($dosificacion['nro_tramite']); ?></td>
				<td><?= escape($dosificacion['nro_autorizacion']); ?></td>
				<td class="text-nowrap"><code><?= base64_decode($dosificacion['llave_dosificacion']); ?></code></td>
				<td><?= date_decode(escape($dosificacion['fecha_limite']), $_institution['formato']); ?></td>
				<td>Ley Nº 453: "<?= escape($dosificacion['leyenda']); ?>"</td>
				<td><?= escape($dosificacion['observacion']); ?></td>
				<td class="text-nowrap <?= $alertas[$estado]; ?>"><?= escape($estado); ?></td>
				<td class="text-nowrap"><?= ($estado == 'En uso') ? (($restante == 0) ? 'Último día' : $restante) : (($estado == 'En espera') ? $vigencia + 1 : ''); ?></td>
				<td><?= escape($dosificacion['nro_facturas']); ?></td>
				<?php if ($permiso_bloquear || $permiso_ver || $permiso_editar || $permiso_eliminar) { ?>
				<td>
					<?php if ($permiso_bloquear) { ?>
						<?php if ($dosificacion['activo'] == 'S') { ?>
						<a href="?/dosificaciones/bloquear/<?= $dosificacion['id_dosificacion']; ?>" class="text-success" data-toggle="tooltip" data-title="Bloquear llave dosificación" data-bloquear="true"><i class="glyphicon glyphicon-check"></i></a>
						<?php } else { ?>
						<a href="?/dosificaciones/bloquear/<?= $dosificacion['id_dosificacion']; ?>" class="text-danger" data-toggle="tooltip" data-title="Desbloquear llave dosificación" data-bloquear="true"><i class="glyphicon glyphicon-unchecked"></i></a>
						<?php } ?>
					<?php } ?>
					<?php if ($permiso_ver) { ?>
					<a href="?/dosificaciones/ver/<?= $dosificacion['id_dosificacion']; ?>" data-toggle="tooltip" data-title="Ver dosificación"><i class="glyphicon glyphicon-search"></i></a>
					<?php } ?>
					<?php if ($permiso_editar) { ?>
					<a href="?/dosificaciones/editar/<?= $dosificacion['id_dosificacion']; ?>" data-toggle="tooltip" data-title="Editar dosificación"><i class="glyphicon glyphicon-edit"></i></a>
					<?php } ?>
					<?php if ($permiso_eliminar) { ?>
					<a href="?/dosificaciones/eliminar/<?= $dosificacion['id_dosificacion']; ?>" data-toggle="tooltip" data-title="Eliminar dosificación" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i></a>
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
		<p>No existen dosificaciones registradas en la base de datos, para crear nuevas dosificaciones hacer clic en el botón nuevo o presionar las teclas <kbd><kbd>alt</kbd> + <kbd>n</kbd></kbd>.</p>
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
		bootbox.confirm('Está seguro que desea eliminar la dosificación?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>

	<?php if ($permiso_bloquear) { ?>
	$('[data-bloquear]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea cambiar el estado de la dosificación?', function (result) {
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
					window.location = '?/dosificaciones/crear';
				break;
			}
		}
	});
	<?php } ?>
	
	<?php if ($dosificaciones) { ?>
	var table = $('#table').DataFilter({
		filter: true,
		name: 'dosificaciones',
		reports: 'excel|word|pdf|html'
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>