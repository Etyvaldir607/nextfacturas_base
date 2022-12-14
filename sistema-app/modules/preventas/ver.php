<?php

// Obtiene el id_proforma
$id_proforma = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la proforma
$proforma = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, e2.nombres nombresv, e2.paterno paternov, e2.materno maternov')
			   ->from('inv_egresos i')
			   ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
			   ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
			   ->join('sys_empleados e2', 'i.vendedor_id = e2.id_empleado', 'left')
			   ->where('id_egreso', $id_proforma)->fetch_first();

// Verifica si existe el proforma
if($_user['rol'] == 'Superusuario'){
	if (!$proforma ) {
		// Error 404
		require_once not_found();
		exit;
	}
} else {
	//if (!$proforma || $proforma['empleado_id'] != $_user['persona_id']) {
	if (!$proforma) {
		// Error 404
		require_once not_found();
		exit;
	}
}

// Obtiene los detalles
$detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')
			   ->from('inv_egresos_detalles d')
			   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
			   ->where('d.egreso_id', $id_proforma)
			   ->where('p.promocion !=', 'si')
			   ->order_by('id_detalle asc')
			   ->fetch();
// echo json_encode($detalles); die();
// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos) || true;
$permiso_mostrar = in_array('mostrar', $permisos);
$permiso_reimprimir = in_array('reimprimir', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-proforma="<?= $id_proforma; ?>" data-servidor="<?= ip_local . name_project . '/proforma.php'; ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Detalle de la preventa</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_imprimir || $permiso_reimprimir || $permiso_eliminar || $permiso_editar || $permiso_crear || $permiso_mostrar) { ?>
	<div class="row">
		<div class="col-sm-3 hidden-xs">
			<div class="text-label">Seleccionar acci??n:</div>
		</div>
		<div class="col-xs-12 col-sm-9 text-right">
			<?php if ($permiso_imprimir) { ?>
			<!--<a href="?/preventas/imprimir_nota/<?= $proforma['id_egreso']; ?>" class="btn btn-default" target="_blank"><i class="glyphicon glyphicon-file"></i><span class="hidden-xs"> Imprimir</span></a>-->
			<?php } ?>
			<?php if ($permiso_reimprimir) { ?>
			<!--<button type="button" class="btn btn-info" data-reimprimir="true"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Reimprimir</span></button>-->
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
			<!--<a href="?/preventas/eliminar/<?= $proforma['id_egreso']; ?>" class="btn btn-danger" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span><span class="hidden-xs"> Eliminar</span></a>-->
			<?php } ?>
			<?php if ($permiso_editar) { ?>
			<button type="button" class="btn btn-warning" data-editar="true"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs"> Editar</span></button>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<a href="?/preventas/seleccionar_almacen" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span><span class="hidden-xs hidden-sm"> Nueva preventa</span></a>
			<?php } ?>
			<?php if ($permiso_mostrar) { ?>
			<a href="?/preventas/mostrar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs hidden-sm"> Listado</span></a>
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
					<h3 class="panel-title"><i class="glyphicon glyphicon-list"></i> Detalle de la preventa</h3>
				</div>
				<div class="panel-body">
					<?php if ($detalles) { ?>
					<div class="table-responsive">
						<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
							<thead>
								<tr class="active">
									<th class="text-nowrap">#</th>
									<th class="text-nowrap">C??digo</th>
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
									<?php $cantidad = escape($detalle['cantidad']); ?>
									<?php $precio = escape($detalle['precio']);
                                    $pr = $db->select('*')->from('inv_productos a')->join('inv_unidades b', 'a.unidad_id = b.id_unidad')->where('a.id_producto',$detalle['producto_id'])->fetch_first();

									// echo json_encode($pr);
                                    if($pr['unidad_id'] == $detalle['unidad_id']){
                                        $unidad = $pr['unidad'];
										// echo json_encode($unidad);
                                    }else{
                                        $pr = $db->select('*')->from('inv_asignaciones a')->join('inv_unidades b', 'a.unidad_id = b.id_unidad')->where(array('a.producto_id'=>$detalle['producto_id'],'a.unidad_id'=>$detalle['unidad_id']))->fetch_first();
                                        $unidad = $pr['unidad'];
										echo json_encode($unidad);
                                        $cantidad = $cantidad/$pr['cantidad_unidad'];
                                    }
                                    ?>
									<?php $importe = $cantidad * $precio; ?>
									<?php $total = $total + $importe; ?>
									<th class="text-nowrap"><?= $nro + 1; ?></th>
									<td class="text-nowrap"><?= escape($detalle['codigo']); ?></td>
									<td class="text-nowrap"><?= escape($detalle['nombre_factura']); ?></td>
									<td class="text-nowrap text-right"><?= $cantidad.' '.$unidad; ?></td>
									<td class="text-nowrap text-right"><?= number_format($precio, 2, ',', '.'); ?></td>
									<td class="text-nowrap text-right"><?= number_format($importe, 2, ',', '.'); ?></td>
								</tr>
								<?php } ?>
							</tbody>
							<tfoot>
								<tr class="active">
									<th class="text-nowrap text-right" colspan="5">Importe total <?= escape($moneda); ?></th>
									<th class="text-nowrap text-right"><?= number_format($total, 2, ',', '.'); ?></th>
								</tr>
							</tfoot>
						</table>
					</div>
					<?php } else { ?>
					<div class="alert alert-danger">
						<strong>Advertencia!</strong>
						<p>Esta preventa no tiene detalle, es muy importante que todas las preventas cuenten con un detalle que especifique la operaci??n realizada.</p>
					</div>
					<?php } ?>
				</div>
			</div>
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-log-out"></i> Informaci??n de la preventa</h3>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-3 control-label">Fecha y hora:</label>
							<div class="col-md-9">
								<?php
						        $fecha_hab=explode(" ",$proforma['fecha_habilitacion']);
                                ?>
								<p class="form-control-static"><?= escape(date_decode($fecha_hab[0], $_institution['formato'])); ?> <small class="text-success"><?= escape($fecha_hab[1]); ?></small></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Cliente:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($proforma['nombre_cliente']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">NIT / CI:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($proforma['nit_ci']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">N??mero de Nota:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($proforma['nro_nota']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Monto total:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape(number_format($proforma['monto_total'],2,',','.') ); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">N??mero de registros:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($proforma['nro_registros']); ?></p>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-md-3 control-label">Forma de Pago:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= ($proforma['plan_de_pagos'] == 'si')?'Plan de Pagos':'Contado'; ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Tipo de entrega:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= ($proforma['distribuir'] == 'S')?'Distribucion':'Entrega Inmediata'; ?></p>
							</div>
						</div>
						
						<div class="form-group">
							<label class="col-md-3 control-label">Almac??n:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($proforma['almacen']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Vendedor:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($proforma['nombresv'] . ' ' . $proforma['paternov'] . ' ' . $proforma['maternov']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Empleado:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($proforma['nombres'] . ' ' . $proforma['paterno'] . ' ' . $proforma['materno']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Observacion:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($proforma['descripcion_venta']); ?></p>
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
		<form method="POST" action="?/preventas/editar" id="form_cliente" class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Editar datos del cliente</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-sm-12">
						<div class="form-group">
							<label for="nit_ci">NIT / CI:</label>
							
							<input type="text" name="id_proforma" value="<?= $proforma['id_egreso']; ?>" class="translate" tabindex="-1" data-validation="required number">
							<input type="text" name="nit_ci" value="<?= $proforma['nit_ci']; ?>" id="nit_ci" class="form-control" autocomplete="off" data-validation="required number">
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<div class="form-group">
							<label for="nombre_cliente">Se??or(es):</label>
							<input type="text" name="nombre_cliente" value="<?= $proforma['nombre_cliente']; ?>" id="nombre_cliente" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./& " data-validation-length="max100">
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
	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Est?? seguro que desea eliminar la proforma y todo su detalle?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>

	<?php if ($permiso_reimprimir) { ?>
	var id_proforma = $('[data-proforma]').attr('data-proforma');

	$('[data-reimprimir]').on('click', function () {
		$('#loader').fadeIn(100);

		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: '?/preventas/obtener',
			data: {
				id_proforma: id_proforma
			}
		}).done(function (proforma) {
			if (proforma) {
				var servidor = $.trim($('[data-servidor]').attr('data-servidor'));

				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: servidor,
					data: proforma
				}).done(function (respuesta) {
					switch (respuesta.estado) {
						case 'success':
							$('#loader').fadeOut(100);
							$.notify({
								title: '<strong>Operaci??n satisfactoria!</strong>',
								message: '<div>Imprimiendo proforma...</div>'
							}, {
								type: 'success'
							});
							break;
						default:
							$('#loader').fadeOut(100);
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
						message: '<div>Ocurri?? un problema en el envio de la informaci??n, vuelva a intentarlo nuevamente y si persiste el problema contactese con el administrador.</div>'
					}, {
						type: 'danger'
					});
				});
			} else {
				$('#loader').fadeOut(100);
				$.notify({
					title: '<strong>Error!</strong>',
					message: '<div>Ocurri?? un problema al obtener los datos de la proforma.</div>'
				}, {
					type: 'danger'
				});
			}
		}).fail(function () {
			$('#loader').fadeOut(100);
			$.notify({
				title: '<strong>Error!</strong>',
				message: '<div>Ocurri?? un problema al obtener los datos de la proforma.</div>'
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