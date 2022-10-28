<?php

// Obtiene el id_proforma
$id_proforma = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la proforma
$proforma =  $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, c.direccion')
                ->from('inv_egresos i')
                ->join('inv_clientes c', 'i.cliente_id = c.id_cliente')
                ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
                ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
                ->where('id_egreso', $id_proforma)
                ->fetch_first();

// Verifica si existe el proforma
if (!$proforma) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los detalles
$detalles =  $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')
                ->from('inv_egresos_detalles d')
                ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
                ->where('d.egreso_id', $id_proforma)
                ->order_by('id_detalle asc')
                ->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_editar = in_array('proformas_editar', $permisos);
$permiso_eliminar = in_array('proformas_eliminar', $permisos);
$permiso_imprimir = in_array('proformas_imprimir', $permisos) || true;
$permiso_listar = in_array('proformas_listar', $permisos);
$permiso_facturar = in_array('proformas_facturar', $permisos);
$permiso_reimprimir = false;
//$permiso_reimprimir = in_array('proformas_obtener', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<style>
.table-display > .thead > .tr,
.table-display > .tbody > .tr,
.table-display > .tfoot > .tr {
	margin-bottom: 15px;
}
.table-display > .thead > .tr > .th,
.table-display > .tbody > .tr > .th,
.table-display > .tfoot > .tr > .th {
	font-weight: bold;
}
@media (min-width: 768px) {
	.table-display {
		display: table;
	}
	.table-display > .thead,
	.table-display > .tbody,
	.table-display > .tfoot {
		display: table-row-group;
	}
	.table-display > .thead > .tr,
	.table-display > .tbody > .tr,
	.table-display > .tfoot > .tr {
		display: table-row;
	}
	.table-display > .thead > .tr > .th,
	.table-display > .thead > .tr > .td,
	.table-display > .tbody > .tr > .th,
	.table-display > .tbody > .tr > .td,
	.table-display > .tfoot > .tr > .th,
	.table-display > .tfoot > .tr > .td {
		display: table-cell;
	}
	.table-display > .tbody > .tr > .td,
	.table-display > .tbody > .tr > .th,
	.table-display > .tfoot > .tr > .td,
	.table-display > .tfoot > .tr > .th,
	.table-display > .thead > .tr > .td,
	.table-display > .thead > .tr > .th {
		padding-bottom: 15px;
		vertical-align: top;
	}
	.table-display > .tbody > .tr > .td:first-child,
	.table-display > .tbody > .tr > .th:first-child,
	.table-display > .tfoot > .tr > .td:first-child,
	.table-display > .tfoot > .tr > .th:first-child,
	.table-display > .thead > .tr > .td:first-child,
	.table-display > .thead > .tr > .th:first-child {
		padding-right: 15px;
	}
}
</style>
<div class="panel-heading" data-proforma="<?= $id_proforma; ?>" data-servidor="<?= ip_local . 'sistema/proforma.php'; ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Detalle de la preventa</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_imprimir || $permiso_reimprimir || $permiso_eliminar || $permiso_editar || $permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-3 hidden-xs">
			<div class="text-label">Seleccionar acción:</div>
		</div>
		<div class="col-xs-12 col-sm-9 text-right">
			<?php if ($permiso_imprimir) { ?>
			<a href="?/operaciones/notas_imprimir/<?= $proforma['id_egreso']; ?>" class="btn btn-default" target="_blank"><i class="glyphicon glyphicon-file"></i><span class="hidden-xs"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_reimprimir) { ?>
			<button type="button" class="btn btn-info" data-reimprimir="true"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Reimprimir</span></button>
			<?php } ?>
			<!-- <?php if ($permiso_facturar) { if(!$proforma['nro_autorizacion']){?> -->
			<a href="?/operaciones/preventa_ver/<?= $proforma['id_egreso']; ?>" class="btn btn-success"><i class="glyphicon glyphicon-qrcode"></i><span class="hidden-xs hidden-sm"> Facturar</span></a>
			<!-- <?php } }?> -->
			<?php if ($permiso_eliminar) { ?>
			<!--a href="?/operaciones/preventas_eliminar/<?= $proforma['id_egreso']; ?>" class="btn btn-danger" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span><span class="hidden-xs hidden-sm"> Eliminar</span></a-->
			<?php } ?>
			<?php if ($permiso_editar) { ?>
			<button type="button" class="btn btn-warning" data-editar="true"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs hidden-sm"> Editar</span></button>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/operaciones/preventas_listar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs hidden-sm"> Listar</span></a>
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
		<div class="col-md-4">
			<div class="panel panel-info">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-menu-hamburger"></span>
						<span>Información sobre la transacción</span>
					</h3>
				</div>
				<div class="panel-body">
					<div class="table-display">
						<div class="tbody">
							<div class="tr">
								<div class="th">Fecha y hora:</div>
								<div class="td"><?= escape(date_decode($proforma['fecha_egreso'], $_institution['formato'])) . ' ' . escape($proforma['hora_egreso']); ?></div>
							</div>
							<div class="tr">
								<div class="th">Cliente:</div>
								<div class="td"><?= escape($proforma['nombre_cliente']); ?></div>
							</div>
							<div class="tr">
								<div class="th">NIT / CI:</div>
								<div class="td"><?= escape($proforma['nit_ci']); ?></div>
							</div>
							<div class="tr">
								<div class="th">Direccion:</div>
								<div class="td"><?= escape($proforma['direccion']); ?></div>
							</div>
							<div class="tr">
								<div class="th">Número de factura:</div>
								<div class="td"><?= escape($proforma['nro_factura']); ?></div>
							</div>
							<div class="tr">
								<div class="th">Monto total:</div>
								<div class="td"><?= escape(number_format( $proforma['monto_total'], 2, ',', '.')); ?></div>
							</div>
							<div class="tr">
								<div class="th">Número de registros:</div>
								<div class="td"><?= escape($proforma['nro_registros']); ?></div>
							</div>
							<div class="tr">
								<div class="th">Almacén:</div>
								<div class="td"><?= escape($proforma['almacen']); ?></div>
							</div>
							<div class="tr">
								<div class="th">Empleado:</div>
								<div class="td"><?= escape($proforma['nombres'] . ' ' . $proforma['paterno'] . ' ' . $proforma['materno']); ?></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-8">
			<div class="panel panel-info">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-option-vertical"></span>
						<span>Detalle de la preventa</span>
					</h3>
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
									<?php $cantidad = escape($detalle['cantidad']); ?>
									<?php $precio = escape($detalle['precio']);
                                    $pr = $db->select('*')->from('inv_productos a')->join('inv_unidades b', 'a.unidad_id = b.id_unidad')->where('a.id_producto',$detalle['producto_id'])->fetch_first();
                                    if($pr['unidad_id'] == $detalle['unidad_id']){
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
                                    ?>
									<?php $importe = $cantidad * $precio; ?>
									<?php $total = $total + $importe; ?>
									<th class="text-nowrap"><?= $nro + 1; ?></th>
									<td class="text-nowrap"><?= escape($detalle['codigo']); ?></td>
									<td class="text-nowrap"><?= escape($detalle['nombre_factura']); ?></td>
									<td class="text-nowrap text-right"><?= $cantidad.' '.$unidad; ?></td>
									<td class="text-nowrap text-right"><?= number_format( $precio, 2, '.', ''); ?></td>
									<td class="text-nowrap text-right"><?= $detalle['descuento']; ?></td>
									<td class="text-nowrap text-right"><?= number_format($importe, 2, ',', '.'); ?></td>
								</tr>
								<?php } ?>
							</tbody>
							<tfoot>
								<tr class="active">
									<th class="text-nowrap" colspan="6">Importe total <?= escape($moneda); ?></th>
									<th class="text-nowrap text-right"><?= number_format($total, 2, ',', '.'); ?></th>
								</tr>
							</tfoot>
						</table>
					</div>
					<?php } else { ?>
					<div class="alert alert-danger">
						<strong>Advertencia!</strong>
						<p>Esta preventa no tiene detalle, es muy importante que todas las preventas cuenten con un detalle que especifique la operación realizada.</p>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Inicio modal cliente -->
<?php if ($permiso_editar) { ?>
<div id="modal_cliente" class="modal fade">
	<div class="modal-dialog">
		<form method="post" action="?/operaciones/preventas_editar" id="form_cliente" class="modal-content">
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
							<label for="nombre_cliente">Señor(es):</label>
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
		bootbox.confirm('Está seguro que desea eliminar la preventa y todo su detalle?', function (result) {
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
			type: 'post',
			dataType: 'json',
			url: '?/operaciones/preventas_obtener',
			data: {
				id_proforma: id_proforma
			}
		}).done(function (proforma) {
			if (proforma) {
				var servidor = $.trim($('[data-servidor]').attr('data-servidor'));

				$.ajax({
					type: 'post',
					dataType: 'json',
					url: servidor,
					data: proforma
				}).done(function (respuesta) {
					$('#loader').fadeOut(100);
					switch (respuesta) {
						case 'success':
							$.notify({
								title: '<strong>Operación satisfactoria!</strong>',
								message: '<div>Imprimiendo preventa...</div>'
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
					message: '<div>Ocurrió un problema al obtener los datos de la preventa.</div>'
				}, {
					type: 'danger'
				});
			}
		}).fail(function () {
			$('#loader').fadeOut(100);
			$.notify({
				title: '<strong>Error!</strong>',
				message: '<div>Ocurrió un problema al obtener los datos de la preventa.</div>'
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