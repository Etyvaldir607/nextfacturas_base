<?php

// Obtiene el id_venta
$id_venta = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la venta
$venta = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')->from('inv_proformas i')->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')->where('id_proforma', $id_venta)->fetch_first();

// Verifica si existe el egreso
if (!$venta) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los detalles
$detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')->from('inv_proformas_detalles d')->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')->where('d.proforma_id', $id_venta)->order_by('id_detalle asc')->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene la dosificacion del periodo actual
$dosificacion = $db->from('inv_dosificaciones')->where('fecha_limite', $venta['fecha_limite'])->fetch_first();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_editar = in_array('facturas_editar', $permisos);
$permiso_imprimir = in_array('facturas_imprimir', $permisos);
$permiso_listar = in_array('facturas_listar', $permisos);
$permiso_reimprimir = in_array('facturas_obtener', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-venta="<?= $id_venta; ?>" data-servidor="<?= ip_local . name_project . '/factura.php'; ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Detalle de venta electrónica</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_reimprimir || $permiso_editar || $permiso_imprimir || $permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<?php if ($permiso_imprimir) { ?>
			<a href="?/operaciones/notas_imprimir/<?= $venta['id_proforma']; ?>" class="btn btn-default" target="_blank"><i class="glyphicon glyphicon-file"></i><span class="hidden-xs"> Exportar</span></a>
			<?php } ?>
			<?php if ($permiso_reimprimir) { ?>
				<?php if ($venta['facturado'] != true) { ?>
					<button type="button" class="btn btn-info" data-reimprimir="true"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs hidden-sm"> Convertir</span></button>
				<?php } ?>
			<?php } ?>
			<?php if ($permiso_editar) { ?>
			<button type="button" class="btn btn-danger" data-editar="true"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs"> Editar</span></button>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/operaciones/proformas_listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs hidden-sm hidden-md"> Listar</span></a>
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
	<div class="row">
		<div class="col-sm-10 col-sm-offset-1">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-list"></i> Detalle de la venta</h3>
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
									<th class="text-nowrap">Descuento (%)</th>
									<th class="text-nowrap">Importe <?= escape($moneda); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php $total = 0; ?>
								<?php foreach ($detalles as $nro => $detalle) { ?>
								<tr>
									<?php 
									$cantidad = escape($detalle['cantidad']); 
									$precio = escape($detalle['precio']);
                                    $pr = $db->select('*')->from('inv_productos a')->join('inv_unidades b', 'a.unidad_id = b.id_unidad')->where('a.id_producto',$detalle['producto_id'])->fetch_first();
                                    

                                     if($pr['unidad_id'] == $detalle['unidad_id']) {
                                        $unidad = $pr['unidad'];
                                    }else{
                                        $pr = $db->select('*')->from('inv_asignaciones a')->join('inv_unidades b', 'a.unidad_id = b.id_unidad')->where(array('a.producto_id'=>$detalle['producto_id'],'a.unidad_id'=>$detalle['unidad_id']))->fetch_first();
                                        //Validacion
										if($pr['cantidad_unidad'])
										{
											$unidad = $pr['unidad'];
                                       		$cantidad = $cantidad / $pr['cantidad_unidad'];
										}
                                    }

									$importe = $cantidad * $precio; 
									$total = $total + $importe;?>
									<th class="text-nowrap"><?= $nro + 1;?></th>
									<td class="text-nowrap"><?= escape($detalle['codigo']); ?></td>
									<td class="text-nowrap"><?= escape($detalle['nombre_factura']); ?></td>
									<td class="text-nowrap text-right"><?= $cantidad.' '.$unidad; ?></td>
									<td class="text-nowrap text-right"><?= $precio; ?></td>
									<td class="text-nowrap text-right"><?= $detalle['descuento']; ?></td>
									<td class="text-nowrap text-right"><?= number_format($importe, 2, '.', ''); ?></td>
								</tr>
								<?php } ?>
							</tbody>
							<tfoot>
								<tr class="active">
									<th class="text-nowrap text-right" colspan="6">Importe total <?= escape($moneda); ?></th>
									<th class="text-nowrap text-right"><?= number_format($total, 2, '.', ''); ?></th>
								</tr>
							</tfoot>
						</table>
					</div>
					<?php } else { ?>
					<div class="alert alert-danger">
						<strong>Advertencia!</strong>
						<p>Esta venta no tiene detalle, es muy importante que todos los egresos cuenten con un detalle que especifique la operación realizada.</p>
					</div>
					<?php } ?>
				</div>
			</div>
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-log-out"></i> Información de la venta</h3>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-3 control-label">Fecha y hora:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape(date_decode($venta['fecha_egreso'], $_institution['formato'])); ?> <small class="text-success"><?= escape($venta['hora_egreso']); ?></small></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Cliente:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nombre_cliente']); ?></p>
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
							<label class="col-md-3 control-label">Almacén:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['almacen']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Empleado:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($venta['nombres'] . ' ' . $venta['paterno'] . ' ' . $venta['materno']); ?></p>
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
		<form method="post" action="?/operaciones/facturas_editar" id="form_cliente" class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Editar datos del cliente</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-sm-12">
						<div class="form-group">
							<label for="nit_ci">NIT / CI:</label>
							<input type="text" name="id_proforma" value="<?= $venta['id_proforma']; ?>" class="translate" tabindex="-1" data-validation="required number">
							<input type="text" name="nit_ci" value="<?= $venta['nit_ci']; ?>" id="nit_ci" class="form-control" autocomplete="off" data-validation="required number">
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<div class="form-group">
							<label for="nombre_cliente">Señor(es):</label>
							<input type="text" name="nombre_cliente" value="<?= $venta['nombre_cliente']; ?>" id="nombre_cliente" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./& " data-validation-length="max100">
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
			url: '?/operaciones/proformas_obtener',
			data: {
				id_proforma: id_venta
			}
		}).done(function (respuesta) {
			console.log(respuesta);
			$('#loader').fadeOut(100);
			if (respuesta != 'error') {
				$.open('?/operaciones/refacturado_imprimir/' + respuesta, true);
				$.notify({
					title: '<strong>Operación satisfactoria!</strong>',
					message: '<div>Generando factura...</div>'
				}, {
					type: 'success'
				});
				setTimeout("location.reload(true);", 100);
			} else {
				$.notify({
					title: '<strong>Advertencia!</strong>',
					message: '<div>Hubo un problema al realizar la conversion de proforma a factura.</div>'
				}, {
					type: 'danger'
				});
			}
			// if (venta) {
			// 	var servidor = $.trim($('[data-servidor]').attr('data-servidor'));

			// 	$.ajax({
			// 		type: 'post',
			// 		dataType: 'json',
			// 		url: 'http://localhost:9000/sistema/factura.php',
			// 		data: venta
			// 	}).done(function (respuesta) {
			// 		console.log(respuesta);
			// 		$('#loader').fadeOut(100);
			// 		switch (respuesta.estado) {
			// 			case 'success':
			// 				$.notify({
			// 					title: '<strong>Operación satisfactoria!</strong>',
			// 					message: '<div>Imprimiendo factura...</div>'
			// 				}, {
			// 					type: 'success'
			// 				});
			// 				break;
			// 			default:
			// 				$.notify({
			// 					title: '<strong>Advertencia!</strong>',
			// 					message: '<div>La impresora no responde, asegurese de que este conectada y vuelva a intentarlo nuevamente.</div>'
			// 				}, {
			// 					type: 'danger'
			// 				});
			// 				break;
			// 		}
			// 	}).fail(function () {
			// 		$('#loader').fadeOut(100);
			// 		$.notify({
			// 			title: '<strong>Error!</strong>',
			// 			message: '<div>Ocurrió un problema en el envio de la información, vuelva a intentarlo nuevamente y si persiste el problema contactese con el administrador.</div>'
			// 		}, {
			// 			type: 'danger'
			// 		});
			// 	});
			// } else {
			// 	$('#loader').fadeOut(100);
			// 	$.notify({
			// 		title: '<strong>Error!</strong>',
			// 		message: '<div>Ocurrió un problema al obtener los datos de la venta.</div>'
			// 	}, {
			// 		type: 'danger'
			// 	});
			// }
		}).fail(function () {
			$('#loader').fadeOut(100);
			$.notify({
				title: '<strong>Error!</strong>',
				message: '<div>Ocurrió un problema al obtener los datos de la venta.</div>'
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