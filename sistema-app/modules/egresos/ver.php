<?php

// Obtiene el id_egreso
$id_egreso = (isset($params[0])) ? $params[0] : 0;

// Obtiene el egreso
$egreso = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')->from('inv_egresos i')->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')->where('id_egreso', $id_egreso)->fetch_first();


// Verifica si existe el egreso
if (!$egreso) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los detalles
$detalles =  $db->select('d.*, p.codigo, p.nombre, p.nombre_factura, SUM(d.cantidad)as cantidad, SUM(d.cantidad*d.precio)as importe')
                ->from('inv_egresos_detalles d')
                ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
                ->where('d.egreso_id', $id_egreso)
                ->group_by('producto_id, lote, vencimiento')
                ->order_by('id_detalle asc')
                ->fetch();

// Obtiene los almacenes
$almacenes = $db->from('inv_almacenes')->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);
$permiso_listar = in_array('listar', $permisos);
$permiso_suprimir = in_array('suprimir', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<b>Detalle de egreso</b>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_crear || $permiso_eliminar || $permiso_imprimir || $permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<?php if ($permiso_eliminar) { ?>
			<a href="?/egresos/eliminar/<?= $egreso['id_egreso']; ?>" class="btn btn-danger" data-eliminar="true">
				<span class="glyphicon glyphicon-trash"></span>
				<span class="hidden-xs hidden-sm">Eliminar</span>
			</a>
			<?php } ?>
			<?php if ($permiso_imprimir) { ?>
			    <?php if ($egreso['tipo'] == 'Egreso especial') { ?>
        			<a href="?/egresos/imprimir_nota/<?= $egreso['id_egreso']; ?>" target="_blank" class="btn btn-info">
        				<span class="glyphicon glyphicon-print"></span>
        				<span class="hidden-xs hidden-sm">Imprimir</span>
        			</a>
			    <?php }else{ ?>
			        <a href="?/egresos/imprimir/<?= $egreso['id_egreso']; ?>" target="_blank" class="btn btn-info">
        				<span class="glyphicon glyphicon-print"></span>
        				<span class="hidden-xs hidden-sm">Imprimir</span>
        			</a>
			    <?php } ?>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/egresos/listar" class="btn btn-primary">
				<span class="glyphicon glyphicon-list-alt"></span>
				<span class="hidden-xs hidden-sm">Listado</span>
			</a>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<div class="btn-group">
				<button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
					<span class="glyphicon glyphicon-plus"></span>
					<span>Egresar</span>
				</button>
				<ul class="dropdown-menu dropdown-menu-right">
					<?php foreach ($almacenes as $elemento) { ?>
					<li><a href="?/egresos/crear/<?= $elemento['id_almacen']; ?>"><span class="glyphicon glyphicon-star"></span> <?= escape($elemento['almacen'] . (($elemento['principal'] == 'S') ? ' (principal)' : '')); ?></a></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<div class="row">
		<div class="col-sm-10 col-sm-offset-1">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><span class="glyphicon glyphicon-list"></span> Detalle del egreso</h3>
				</div>
				<div class="panel-body">
					<?php if ($detalles) { ?>
					<div class="table-responsive">
						<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
							<thead>
								<tr class="active">
									<th class="text-nowrap">#</th>
									<th class="text-nowrap">Código</th>
									<th class="text-nowrap">Nombre</th>
									<th class="text-nowrap">Lote</th>
									<th class="text-nowrap">Vencimiento</th>
									<th class="text-nowrap">Cantidad</th>
									<th class="text-nowrap">Precio <?= escape($moneda); ?></th>
									<th class="text-nowrap">Importe <?= escape($moneda); ?></th>
									<?php if ($permiso_suprimir10) { ?>
									<th class="text-nowrap">Opciones</th>
									<?php } ?>
								</tr>
							</thead>
							<tbody>
								<?php $total = 0; ?>
								<?php foreach ($detalles as $nro => $detalle) { ?>
								<tr>
									<?php $cantidad = escape($detalle['cantidad']); ?>
									<?php $importe = escape($detalle['importe']); ?>
									<?php $precio = $importe / $cantidad; ?>
									<?php //$importe = $cantidad * $precio; ?>
									<?php $total = $total + $importe; ?>
									<th class="text-nowrap"><?= $nro + 1; ?></th>
									<td class="text-nowrap"><?= escape($detalle['codigo']); ?></td>
									<td class="text-nowrap"><?= escape($detalle['nombre_factura']); ?></td>
									<td class="text-nowrap"><?= escape($detalle['lote']); ?></td>
									<td class="text-nowrap"><?= escape(date_decode($detalle['vencimiento'], $_institution['formato'])); ?></td>
									<td class="text-nowrap text-right"><?= number_format($cantidad, 0, ',', '.'); ?></td>
									<td class="text-nowrap text-right"><?= number_format($precio, 2, ',', '.'); ?></td>
									<td class="text-nowrap text-right"><?= number_format($importe, 2, ',', '.'); ?></td>
									<?php if ($permiso_suprimir10) { ?>
									<td class="text-nowrap">
										<a href="?/egresos/suprimir/<?= $egreso['id_egreso']; ?>/<?= $detalle['id_detalle']; ?>" data-toggle="tooltip" data-title="Eliminar detalle" data-suprimir="true"><span class="glyphicon glyphicon-trash"></span></a>
									</td>
									<?php } ?>
								</tr>
								<?php } ?>
							</tbody>
							<tfoot>
								<tr class="active">
									<th class="text-nowrap text-right" colspan="7">Importe total <?= escape($moneda); ?></th>
									<th class="text-nowrap text-right"><?= number_format($total, 2, ',', '.'); ?></th>
									<?php if ($permiso_suprimir10) { ?>
									<th class="text-nowrap">Opciones</th>
									<?php } ?>
								</tr>
							</tfoot>
						</table>
					</div>
					<?php } else { ?>
					<div class="alert alert-danger">
						<strong>Advertencia!</strong>
						<p>Este egreso no tiene detalle, es muy importante que todos los egresos cuenten con un detalle que especifique la operación realizada.</p>
					</div>
					<?php } ?>
				</div>
			</div>
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><span class="glyphicon glyphicon-log-out"></span> Información del egreso</h3>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-3 control-label">Fecha y hora:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape(date_decode($egreso['fecha_egreso'], $_institution['formato'])); ?> <small class="text-success"><?= escape($egreso['hora_egreso']); ?></small></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Almacén:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($egreso['almacen']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Tipo de egreso:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($egreso['tipo']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Descripción:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($egreso['descripcion_venta']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Monto total:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= number_format($egreso['monto_total'], 2, ',', '.'); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Número de registros:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($egreso['nro_registros']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Empleado:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($egreso['nombres'] . ' ' . $egreso['paterno'] . ' ' . $egreso['materno']); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/bootstrap-notify.min.js"></script>
<script>
$(function () {
	<?php if ($permiso_crear) { ?>
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'n':
					e.preventDefault();
					window.location = '?/egresos/crear';
				break;
			}
		}
	});
	<?php } ?>

	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar el egreso y todo su detalle?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>

	<?php if ($permiso_suprimir) { ?>
	$('[data-suprimir]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar el detalle del egreso?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>