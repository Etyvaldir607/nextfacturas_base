<?php

// Obtiene el id_venta
$id_venta = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la venta
$venta = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')->from('inv_egresos i')->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')->where('id_egreso', $id_venta)->fetch_first();

// Verifica si existe el egreso
if($_user['rol'] == 'Superusuario'){
	if (!$venta) {
		// Error 404
		require_once not_found();
		exit;
	}
} else {
	if (!$venta || $venta['empleado_id'] != $_user['persona_id']) {
		// Error 404
		require_once not_found();
		exit;
	}
}

// Obtiene los detalles
$detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')->from('inv_egresos_detalles d')->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')->where('d.egreso_id', $id_venta)->order_by('id_detalle asc')->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene la dosificacion del periodo actual
$dosificacion = $db->from('inv_dosificaciones')->where('fecha_limite', $venta['fecha_limite'])->fetch_first();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_mostrar = in_array('mostrar', $permisos);
$permiso_reimprimir = in_array('reimprimir', $permisos);


?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading" data-venta="<?= $id_venta; ?>" data-servidor="<?= ip_local . name_project . '/factura.php'; ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Detalle de venta electrónica</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_reimprimir || $permiso_editar || $permiso_crear || $permiso_mostrar) { ?>
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<?php if ($permiso_reimprimir) { ?>
			<button type="button" class="btn btn-info" data-reimprimir="true"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs hidden-sm"> Reimprimir</span></button>
			<?php } ?>
			<?php if ($permiso_editar) { ?>
			<button type="button" class="btn btn-danger" data-editar="true"><i class="glyphicon glyphicon-edit"></i><span class="hidden-xs"> Editar</span></button>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<a href="?/electronicas/seleccionar_almacen" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span><span class="hidden-xs"> Vender</span></a>
			<?php } ?>
			<?php if ($permiso_mostrar) { ?>
			<a href="?/electronicas/mostrar" class="btn btn-primary"><i class="glyphicon glyphicon-list-alt"></i><span class="hidden-xs hidden-sm hidden-md"> Ventas personales</span></a>
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

	<div class="container outer-section" >
        <div id="print-area">
            <div class="row pad-top font-big">
	            <div class="col-lg-4 col-md-4 col-sm-4">
	              <img src="<?= imgs.'/image.jpg'?>" width="75" height="75" alt="Logo sistemas web" />
	            </div>
	            <div class="col-lg-4 col-md-4 col-sm-4">
	                <strong>Casa Matriz : </strong><?= $_institution['razon_social']?>
	                <br />
	                <strong>NIT :</strong> <?= $_institution['nit'];?> <br />
	            </div>
	            <div class="col-lg-4 col-md-4 col-sm-4">
                    <strong><?php echo $_institution['nombre'];?></strong>
                    <br />
                    Dirección : <?= $_institution['direccion'];?> 
                </div>
    		</div>
            
            <div class="row ">
				<hr />
	            <div class="col-lg-6 col-md-6 col-sm-6">
	                <h2>Detalles del cliente :</h2>
	                <span id="direccion"></span>
	                <h4><strong>NIT / CI: </strong><span id="nit_cliente"><?= escape($venta['nit_ci']); ?></span></h4>
	                <h4><strong>Nombre: </strong><span id="nombre_cliente"><?= escape($venta['nombre_cliente']);?></span></h4>
	            </div>
	            <div class="col-lg-6 col-md-6 col-sm-6">
	                <h2>Información de la venta :</h2>
	                <h4><strong>Número de factura : </strong><?= escape($venta['nro_factura']); ?></h4>
	                <h4><strong>Fecha y hora : </strong> <?= escape(date_decode($venta['fecha_egreso'], $_institution['formato'])); ?> <small class="text-success"><?= escape($venta['hora_egreso']); ?></small></h4>
	                <h4><strong>Descripción : </strong><?= escape($venta['descripcion']); ?></h4>
	                <h4><strong>Monto total : </strong><?= escape($venta['monto_total']); ?></h4>
					<h4><strong>Almacén : </strong><?= escape($venta['almacen']); ?></h4> 
					<h4><strong>Observacion : </strong><?= escape($venta['descripcion_venta']); ?></h4> 
	            </div>
            </div>
         
            <div class="row">
			<hr />
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="table-responsive">
                        <table class="table table-striped  table-hover">
                            <thead>
                                <tr>
                                    <th class='text-center'>Item</th>
									<th>Código</th>
									<th class='text-center'>Nombre</th>
                                    <th class='text-right'>Cantidad</th>
                                    <th class='text-right'>Precio</th>
                                    <th class='text-right'>Descuento</th>
									<th class='text-right'>Importe</th>
                                </tr>
                            </thead>
                            <tbody class='items'>
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
                                        $unidad = $pr['unidad'];
                                        $cantidad = $cantidad/$pr['cantidad_unidad'];
                                    }
                                    ?>
									<?php $importe = $cantidad * $precio; ?>
									<?php $total = $total + $importe; ?>
									<th class="text-nowrap"><?= $nro + 1; ?></th>
									<td class="text-nowrap"><?= escape($detalle['codigo']); ?></td>
									<td class="text-nowrap"><?= escape($detalle['nombre_factura']); ?></td>
									<td class="text-nowrap text-right"><?= $cantidad.' '.$unidad; ?></td>
									<td class="text-nowrap text-right"><?= $precio; ?></td>
									<td class="text-nowrap text-right"><?= $detalle['descuento']; ?></td>
									<td class="text-nowrap text-right"><?= number_format($importe, 2, '.', ''); ?></td>
								</tr>
								<?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
       <div class="row"> <hr /></div>
    </div>

</div>

<!-- Inicio modal cliente -->
<?php if ($permiso_editar) { ?>
<div id="modal_cliente" class="modal fade">
	<div class="modal-dialog">
		<form method="POST" action="?/electronicas/editar" id="form_cliente" class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">Editar datos del cliente</h4>
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
			type: 'POST',
			dataType: 'json',
			url: '?/electronicas/obtener',
			data: {
				id_venta: id_venta
			}
		}).done(function (venta) {
			if (venta) {
				var servidor = $.trim($('[data-servidor]').attr('data-servidor'));

				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: servidor,
					data: venta
				}).done(function (respuesta) {
					switch (respuesta.estado) {
						case 'success':
							$('#loader').fadeOut(100);
							$.notify({
								title: '<strong>Operación satisfactoria!</strong>',
								message: '<div>Imprimiendo factura...</div>'
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
						message: '<div>Ocurrió un problema en el envio de la información, vuelva a intentarlo nuevamente y si persiste el problema contactese con el administrador.</div>'
					}, {
						type: 'danger'
					});
				});
			} else {
				$('#loader').fadeOut(100);
				$.notify({
					title: '<strong>Error!</strong>',
					message: '<div>Ocurrió un problema al obtener los datos de la venta.</div>'
				}, {
					type: 'danger'
				});
			}
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