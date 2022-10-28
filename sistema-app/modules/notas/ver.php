<?php

// Obtiene el id_venta
$id_venta = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la venta
$venta = $db->select('i.*, f.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, v.nombres nombresv, v.paterno paternov, v.materno maternov,cl.cliente')
            ->from('inv_egresos i')
            ->join('inv_egresos_facturas f', 'i.id_egreso = f.egreso_id', 'left')
            ->join('inv_clientes cl', 'i.cliente_id = cl.id_cliente', 'left')
            ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
            ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
            ->join('sys_empleados v', 'i.vendedor_id = v.id_empleado', 'left')
            ->where('id_egreso', $id_venta)
            ->fetch_first();

// Verifica si existe el egreso
if($_user['rol'] == 'Superusuario'){
	if (!$venta) {
		// Error 404
		require_once not_found();
		exit;
	}
} else {
	//if (!$venta || $venta['vendedor_id'] != $_user['persona_id']) {
	if (!$venta) {
		// Error 404
		require_once not_found();
		exit;
	}
}

// Obtiene los detalles
$detalles =  $db->query('select d.*, SUM(d.cantidad) as cantidad, d.unidad_id as unidad_otra, p.codigo, p.nombre, p.nombre_factura
                        from inv_egresos_detalles d
                        left join inv_productos p ON d.producto_id = p.id_producto
                        where d.egreso_id="'.$id_venta.'"
                        group by precio, producto_id, lote, vencimiento
                        order by id_detalle asc
                        ')->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene la dosificacion del periodo actual
$dosificacion = $db->from('inv_dosificaciones')->where('fecha_limite', $venta['fecha_limite'])->fetch_first();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_editar = in_array('editar', $permisos);
$permiso_mostrar = in_array('mostrar', $permisos);
$permiso_reimprimir = in_array('obtener', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-venta="<?= $id_venta; ?>" data-servidor="<?= ip_local . 'servidor/nota.php'; ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<b>Detalle de nota de venta</b>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_reimprimir || $permiso_editar || $permiso_mostrar) { ?>
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<?php if ($permiso_reimprimir && $venta['preventa'] != 'anulado') { ?>
			<!--<button type="button" class="btn btn-info" data-reimprimir="true"><span class="glyphicon glyphicon-print"></span><span class="hidden-xs hidden-sm"> Reimprimir</span></button>-->
			<a href="?/notas/imprimir_nota/<?= $id_venta; ?>" target="_blank" class="btn btn-info"><span class="glyphicon glyphicon-print"></span><span class="hidden-xs hidden-sm"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_editar && $venta['preventa'] != 'anulado') { ?>
			<button type="button" class="btn btn-danger" data-editar="true"><span class="glyphicon glyphicon-edit"></span><span class="hidden-xs"> Modificar</span></button>
			<?php } ?>
			<?php if ($permiso_mostrar) { ?>
			<a href="?/notas/mostrar" class="btn btn-primary"><span class="glyphicon glyphicon-list-alt"></span><span class="hidden-xs hidden-sm hidden-md"> Notas de venta</span></a>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<div class="row">
		<div class="col-sm-10 col-sm-offset-1">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><span class="glyphicon glyphicon-list"></span> Detalle de la nota de venta</h3>
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
									<th class="text-nowrap">Cantidad</th>
									<th class="text-nowrap">Precio <?= escape($moneda); ?></th>
									<th class="text-nowrap">Importe <?= escape($moneda); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php $total = 0; ?>
								<?php foreach ($detalles as $nro => $detalle) { ?>
								<tr>
									<?php $cantidad = escape($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_otra'])); ?>
									<?php $precio = escape($detalle['precio']); ?>
									<?php $importe = $cantidad * $precio; ?>
									<?php $total = $total + $importe; ?>
									<th class="text-nowrap"><?= $nro + 1; ?></th>
									<td class="text-nowrap"><?= escape($detalle['codigo']); ?></td>
									<td class="text-nowrap"><?= escape($detalle['nombre_factura']); ?></td>
									<td class="text-nowrap text-right"><?= $cantidad . ' '.nombre_unidad($db,$detalle['unidad_otra']) ?></td>
									<td class="text-nowrap text-right"><?= $precio; ?></td>
									<td class="text-nowrap text-right"><?= number_format($importe, 2, '.', ''); ?></td>
								</tr>
								<?php } ?>
							</tbody>
							<tfoot>
								<tr class="active">
									<th class="text-nowrap text-right" colspan="5">Importe total <?= escape($moneda); ?></th>
									<th class="text-nowrap text-right"><?= number_format($total, 2, '.', ''); ?></th>
								</tr>
							</tfoot>
						</table>
					</div>
					<?php } else { ?>
					<div class="alert alert-danger">
						<strong>Advertencia!</strong>
						<p>Esta nota de venta no tiene detalle, es muy importante que todos los egresos cuenten con un detalle que especifique la operación realizada.</p>
					</div>
					<?php } ?>
				</div>
			</div>
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><span class="glyphicon glyphicon-log-out"></span> Información de la nota de venta</h3>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-3 control-label">Fecha y hora:</label>
							<div class="col-md-9">
								
								<?php $Auxv=explode(" ",$venta['fecha_habilitacion']); ?>
								<p class="form-control-static"><?= escape(date_decode($Auxv[0], $_institution['formato'])); ?> <small class="text-success"><?= escape($Auxv[1]); ?></small></p>
								
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Cliente:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['cliente']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">NIT / CI:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nit_ci']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Tipo de egreso:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['tipo']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Número de nota:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nro_nota']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Número de factura:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nro_factura']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Descripción:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['descripcion']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Monto total:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['monto_total']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Código de control:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['codigo_control']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Número de registros:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nro_registros']); ?></p>
							</div>
						</div>

						<div class="form-group">
							<label class="col-md-3 control-label">Forma de Pago:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= ($venta['plan_de_pagos'] == 'si')?'Plan de Pagos':'Contado'; ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Tipo de entrega:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= ($venta['distribuir'] == 'S')?'Distribucion':'Entrega Inmediata'; ?></p>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-md-3 control-label">Almacén:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['almacen']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Vendedor:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nombresv'] . ' ' . $venta['paternov'] . ' ' . $venta['maternov']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Operador:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nombres'] . ' ' . $venta['paterno'] . ' ' . $venta['materno']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Observacion:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['descripcion_venta']); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Inicio modal cliente -->
<?php if ($permiso_editar) { ?>
<div id="modal_cliente" class="modal fade">
	<div class="modal-dialog">
		<form method="post" action="?/notas/editar" id="form_cliente" class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Modificar datos del cliente</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-sm-12">
						<div class="form-group">
							<label for="nit_ci">NIT / CI:</label>
							<input type="text" name="id_egreso" value="<?= $venta['id_egreso']; ?>" class="translate" tabindex="-1" data-validation="required number">
							<input type="text" name="nit_ci" value="<?= $venta['nit_ci']; ?>" id="nit_ci" class="form-control" autocomplete="off" data-validation="required number">
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<div class="form-group">
							<label for="nombre_cliente">Señor(es):</label>
							<input type="text" name="nombre_cliente" value="<?= $venta['cliente']; ?>" id="nombre_cliente" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./& " data-validation-length="max100">
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="submit" class="btn btn-primary">
					<span class="glyphicon glyphicon-ok"></span>
					<span>Guardar</span>
				</button>
				<button type="button" class="btn btn-default" data-cancelar="true">
					<span class="glyphicon glyphicon-remove"></span>
					<span>Cancelar</span>
				</button>
			</div>
		</form>
	</div>
</div>
<?php } ?>
<!-- Fin modal cliente -->

<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/bootstrap-notify.min.js"></script>
<script>
$(function () {
	<?php if ($permiso_reimprimir) { ?>
	var id_venta = $('[data-venta]').attr('data-venta');

	$('[data-reimprimir]').on('click', function () {
		$('#loader').fadeIn(100);

		$.ajax({
			type: 'post',
			dataType: 'json',
			url: '?/notas/obtener',
			data: {
				id_venta: id_venta
			}
		}).done(function (venta) {
			if (venta) {
				var servidor = $.trim($('[data-servidor]').attr('data-servidor'));

				$.ajax({
					type: 'post',
					dataType: 'json',
					url: servidor,
					data: venta
				}).done(function (respuesta) {
					$('#loader').fadeOut(100);
					switch (respuesta.estado) {
						case 'success':
							$.notify({
								title: '<strong>Operación satisfactoria!</strong>',
								message: '<div>Imprimiendo factura...</div>'
							}, {
								type: 'success'
							});
							break;
						default:
							$.notify({
								title: '<strong>Advertencia!</strong>',
								message: '<div>La impresora no responde, asegurese de que este conectada y vuelva a intentarlo nuevamente.</div>'
							}, {
								type: 'danger'
							});
							break;
					}
				}).fail(function () {
					$('#loader').fadeOut(100);
					$.notify({
						title: '<strong>Error!</strong>',
						message: '<div>Ocurrió un problema en el envio de la información, vuelva a intentarlo nuevamente y si persiste el problema contactese con el administrador.</div>'
					}, {
						type: 'danger'
					});
				});
			} else {
				$('#loader').fadeOut(100);
				$.notify({
					title: '<strong>Error!</strong>',
					message: '<div>Ocurrió un problema al obtener los datos de la nota de venta.</div>'
				}, {
					type: 'danger'
				});
			}
		}).fail(function () {
			$('#loader').fadeOut(100);
			$.notify({
				title: '<strong>Error!</strong>',
				message: '<div>Ocurrió un problema al obtener los datos de la nota de venta.</div>'
			}, {
				type: 'danger'
			});
		});
	});
	<?php } ?>

	<?php if ($permiso_editar) { ?>
	$.validate({
		modules: 'basic'
	});

	var $modal_cliente = $('#modal_cliente');
	var $form_cliente = $('#form_cliente');

	$modal_cliente.on('hidden.bs.modal', function () {
		$form_cliente.trigger('reset');
	});

	$modal_cliente.on('shown.bs.modal', function () {
		$modal_cliente.find('.form-control:first').focus();
	});

	$modal_cliente.find('[data-cancelar]').on('click', function () {
		$modal_cliente.modal('hide');
	});

	$('[data-editar]').on('click', function () {
		$modal_cliente.modal({
			backdrop: 'static'
		});
	});
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>