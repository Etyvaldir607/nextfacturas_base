<?php

// Obtiene el id_ingreso
$id_ingreso = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene los ingreso
$ingreso = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')
			  ->from('inv_ingresos i')
			  ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
			  ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
			  ->where('id_ingreso', $id_ingreso)
	 		  ->fetch_first();

$id_almacen=$ingreso['almacen_id'];
//$tipo_cambio=$ingreso['tipo_cambio'];
$tipo_cambio=(isset($ingreso['tipo_cambio'])) ? $ingreso['tipo_cambio']: 0;
//var_dump($tipo_cambio);exit();
// Verifica si existe el ingreso
if (!$ingreso) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los detalles
$detalles = $db->select('d.*, p.codigo, p.nombre')
			   ->from('inv_ingresos_detalles d')
			   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
			   ->where('d.ingreso_id', $id_ingreso)
			   ->order_by('id_detalle asc')
			   ->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')
			 ->where('oficial', 'S') 
			 ->fetch_first();

$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

if ($id_almacen != 0) {
	// Obtiene los productos
	$productos = $db->query("select p.id_producto, p.codigo, p.nombre, p.nombre_factura, p.cantidad_minima, p.con_factura, p.sin_factura, p.imagen, p.precio_actual, ifnull(e.cantidad_ingresos, 0) as cantidad_ingresos, ifnull(s.cantidad_egresos, 0) as cantidad_egresos, u.unidad, u.sigla, c.categoria from inv_productos p left join (select d.producto_id, sum(d.cantidad) as cantidad_ingresos from inv_ingresos_detalles d left join inv_ingresos i on i.id_ingreso = d.ingreso_id where i.almacen_id = $id_almacen group by d.producto_id ) as e on e.producto_id = p.id_producto left join (select d.producto_id, sum(d.cantidad) as cantidad_egresos from inv_egresos_detalles d left join inv_egresos e on e.id_egreso = d.egreso_id where e.almacen_id = $id_almacen group by d.producto_id ) as s on s.producto_id = p.id_producto left join inv_unidades u on u.id_unidad = p.unidad_id left join inv_categorias c on c.id_categoria = p.categoria_id")->fetch();
} else {
	$productos = null;
}

//var_dump($productos);exit();
// Obtiene los permisos
$permisos = explode(',', PERMITS);

// Almacena los permisos en variables
$permiso_crear = in_array(FILE_CREATE, $permisos);
$permiso_eliminar = in_array(FILE_DELETE, $permisos);
$permiso_imprimir = in_array(FILE_PRINT, $permisos);
$permiso_listar = in_array(FILE_LIST, $permisos);
$permiso_suprimir = in_array('suprimir', $permisos);
$permiso_modificar = in_array('modificar', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="<?= ICON_PANEL; ?>"></span>
		<strong>Modificar ingreso</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_crear || $permiso_eliminar || $permiso_imprimir || $permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<?php if ($permiso_crear) { ?>
			<a href="?/ingresos/crear" class="btn btn-success"><i class="<?= ICON_CREATE; ?>"></i><span class="hidden-xs hidden-sm"> Nuevo</span></a>
			<?php } ?>
			<?php if ($permiso_eliminar) { ?>
			<a href="?/ingresos/eliminar/<?= $ingreso['id_ingreso']; ?>" class="btn btn-danger" data-eliminar="true"><i class="<?= ICON_DELETE; ?>"></i><span class="hidden-xs hidden-sm"> Eliminar</span></a>
			<?php } ?>
			<?php if ($permiso_imprimir) { ?>
			<a href="?/ingresos/imprimir/<?= $ingreso['id_ingreso']; ?>" target="_blank" class="btn btn-info"><i class="<?= ICON_PRINT; ?>"></i><span class="hidden-xs hidden-sm"> Imprimir</span></a>
			<?php } ?>
			<?php if ($permiso_listar) { ?>
			<a href="?/ingresos/listar" class="btn btn-primary"><i class="<?= ICON_LIST; ?>"></i><span class="hidden-xs hidden-sm <?= ($permiso_imprimir) ? 'hidden-md' : ''; ?>"> Listado</span></a>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<?php if (isset($_SESSION[TEMPORARY])) { ?>
	<div class="alert alert-<?= $_SESSION[TEMPORARY]['alert']; ?>">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong><?= $_SESSION[TEMPORARY]['title']; ?></strong>
		<p><?= $_SESSION[TEMPORARY]['message']; ?></p>
	</div>
	<?php unset($_SESSION[TEMPORARY]); ?>
	<?php } ?>
	<div class="row">
		<div class="col-sm-10 col-sm-offset-1">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-list"></i> Detalle del ingreso</h3>
				</div>
				<div class="panel-body">
					<?php if ($detalles) { ?>
					<form method="POST" action="?/ingresos/guardar-cambio" id="formulario" class="form-horizontal">
                        <input type="hidden" value="<?= $id_ingreso; ?>" name="id_ingreso" id="id_ingreso">

						<div class="form-group">
							<label for="cambio" class="col-sm-4 control-label">Tipo de cambio:</label>
							<div class="col-sm-8">
								<input type="text" value="<?= $tipo_cambio;?>" name="cambio" id="cambio" class="form-control" data-validation="required number" data-validation-allowing="float" onkeyup="calcular_estimado()">
							</div>
						</div>
                        
						<div class="table-responsive margin-none">
							<table id="compras" class="table table-bordered table-condensed table-striped table-hover margin-none">
								<thead>
									<tr class="active">
										<th class="text-nowrap">#</th>
										<th class="text-nowrap">CÓDIGO</th>
										<th class="text-nowrap">NOMBRE</th>
										<th class="text-nowrap">CANTIDAD</th>
										<th class="text-nowrap">COSTO ($us.)</th>
										<th class="text-nowrap">COSTO (Bs.)</th>
										<th class="text-nowrap">ESTIMADO (Bs.)</th>
										<th class="text-nowrap">IMPORTE</th>
										
									</tr>
								</thead>
								<tfoot>
									<tr class="active">
										<th class="text-nowrap text-right" colspan="7">IMPORTE TOTAL ($us.)</th>
										<th class="text-nowrap text-right" data-subtotal="">0.00</th>
										
									</tr>
								</tfoot>
								<tbody>
									<?php $total = 0; ?>
									<?php foreach ($detalles as $nro => $detalle) { ?>
										<?php $cantidad = escape($detalle['cantidad']); ?> 
										<?php $costo = escape($detalle['costo']); ?>
										<?php $importe = $cantidad * $costo; ?>
										<?php $total = $total + $importe; ?>
									<tr class="active" data-producto="<?= $detalle['producto_id']; ?>">

										<input type="hidden" value="<?= escape($detalle['id_detalle']); ?>" name="id_detalle[]" id="id_detalle">
										<td class="text-nowrap"><?= $nro + 1; ?></td>
										<td class="text-nowrap text-middle width-collapse"><?= escape($detalle['codigo']); ?>
										<input type="text" value="<?= $detalle['producto_id']; ?>" name="productos[]" class="translate" tabindex="-1">
										<input type="text" value="0.00" name="estimados[]" class="translate" tabindex="-1" data-estimados="">
										<input type="text" value="0.00" name="bolivianos[]" class="translate" tabindex="-1" data-bolivianos="">
									    </td>
										<td class="text-middle"><?= escape($detalle['nombre']); ?></td>
										<td class="text-middle width-collapse">
										<input type="text" value="<?= $cantidad; ?>" name="cantidades[]" class="form-control text-right" style="width: 100px;" maxlength="7" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-error-msg="Debe ser número entero positivo" onkeyup="calcular_importe(<?= $detalle['producto_id']; ?>)">
										</td>
										<td class="text-middle width-collapse">
										<input type="text" value="0.00" name="costos[]" class="form-control text-right" style="width: 100px;" autocomplete="off" data-costo="" data-validation="required number" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="Debe ser número decimal positivo" onkeyup="calcular_importe(<?= $detalle['producto_id']; ?>);calcular_estimado();" onblur="redondear_importe(<?= $detalle['producto_id']; ?>)">
										</td>
										<td class="text-nowrap text-middle text-right width-collapse" data-boliviano=""><?= $costo; ?></td>

									    <?php foreach ($productos as $nro => $producto) { ?>
	                                    	<?php if ($detalle['producto_id']==$producto['id_producto']) { ?>
											<td class="text-nowrap text-middle text-right width-collapse" data-estimado="" data-factor="<?=  $producto['con_factura']; ?>">0.00</td>
	                                        <?php } ?>
	                                    <?php } ?>
										<td class="text-nowrap text-middle text-right width-collapse" data-importe="">0.00</td>
									</tr>	
									<?php } ?>
								</tbody>
							</table>
						</div>

						<div class="form-group">
							<div class="col-xs-12">
								<input type="text" name="nro_registros" value="0" class="translate" tabindex="-1" data-compras="" data-validation="required number" data-validation-allowing="range[1;50]" data-validation-error-msg="Debe existir como mínimo 1 producto y como máximo 50 productos">
								<input type="text" name="monto_total" value="0" class="translate" tabindex="-1" data-total="" data-validation="required number" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="El costo total de la compra debe ser mayor a cero y menor a 1000000.00">
							</div>
						</div>

						<div class="form-group text-right"><br>
							<div class="col-sm-12">									
								<button type="submit" class="btn btn-primary">
									<span class="<?= ICON_SUBMIT; ?>"></span>
									<span>Guardar</span>
								</button>
							</div>
						</div>

					</form>
					<?php } else { ?>
					<div class="alert alert-danger">
						<strong>Advertencia!</strong>
						<p>Este ingreso no tiene detalle, es muy importante que todos los ingresos cuenten con un detalle de la compra.</p>
					</div>
					<?php } ?>
				</div>
			</div>
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-log-in"></i> Información del ingreso</h3>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-3 control-label">Fecha y hora:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape(date_decode($ingreso['fecha_ingreso'], $_institution['formato'])); ?> <small class="text-success"><?= escape($ingreso['hora_ingreso']); ?></small></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Proveedor:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($ingreso['nombre_proveedor']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Tipo de ingreso:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($ingreso['tipo']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Descripción:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($ingreso['descripcion']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Monto total:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($ingreso['monto_total']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Número de registros:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($ingreso['nro_registros']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Almacén:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($ingreso['almacen']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Empleado:</label>
							<div class="col-md-9">
								<p class="form-control-static"><?= escape($ingreso['nombres'] . ' ' . $ingreso['paterno'] . ' ' . $ingreso['materno']); ?></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="<?= JS; ?>/jquery.form-validator.min.js"></script>
<script src="<?= JS; ?>/jquery.form-validator.es.js"></script>
<script>
$(function () {
	$.validate({
		modules: 'basic'
	});
	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar el ingreso?', function (result) {
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
		bootbox.confirm('Está seguro que desea eliminar el detalle del ingreso?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php } ?>


});

function calcular() {
	var $cambio = $('#cambio');
	var $costos = $('#costos');
	var $cantidad = $('#cantidad');
	cantidad = $.trim($cantidad.val());
	cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
	console.log('sdkjfh');
	var cambio = $.trim($cambio.val());
	cambio = ($.isNumeric(cambio)) ? parseFloat(cambio) : 0;
	var costos = $.trim($costos.val());
	costos = ($.isNumeric(costos)) ? parseFloat(costos) : 0;
	aumentado = costos * cambio;
	aumentado = aumentado.toFixed(2);
	importe = cantidad * costos;
	importe = importe.toFixed(2);
	// estimado = costos * cambio * factor;
	// estimado = estimado.toFixed(2);
	$('#monto').val(aumentado);
	$('#costo-boliviano').html(aumentado);
	$('#costo-boliviano_t').html(aumentado);

	$('#monto').val(aumentado);
	$('#importe').val(importe);
	$('#importe_t').html(importe);
    calcular_total();

	//$(this).parent().find('[data-boliviano]').val(estimado);
	//$(this).parent().find('[data-estimados]').val(estimado);
	//$(this).text(estimado);
}
function redondear_importe(id_producto) {
	var $producto = $('[data-producto=' + id_producto + ']');
	var $costo = $producto.find('[data-costo]');
	var costo;

	costo = $.trim($costo.val());
	costo = ($.isNumeric(costo)) ? parseFloat(costo).toFixed(2) : costo;
	$costo.val(costo);

	calcular_importe(id_producto);
}

function calcular_importe(id_producto) {
	var $producto = $('[data-producto=' + id_producto + ']');
	var $cantidad = $producto.find('[data-cantidad]');
	var $costo = $producto.find('[data-costo]');
	var $importe = $producto.find('[data-importe]');
	var cantidad, costo, importe;

	cantidad = $.trim($cantidad.val());
	cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
	costo = $.trim($costo.val());
	costo = ($.isNumeric(costo)) ? parseFloat(costo) : 0.00;
	importe = cantidad * costo;
	importe = importe.toFixed(2);
	$importe.text(importe);

	calcular_total();
}

function calcular_total() {
	var $compras = $('#compras tbody');
	var $total = $('[data-subtotal]:first');
	var $importes = $compras.find('[data-importe]');
	var importe, total = 0;

	$importes.each(function (i) {
		importe = $.trim($(this).text());
		importe = parseFloat(importe);
		total = total + importe;
	});

	$total.text(total.toFixed(2));
	$('[data-compras]:first').val($importes.size()).trigger('blur');
	$('[data-total]:first').val(total.toFixed(2)).trigger('blur');
}

function calcular_estimado() {
	var $cambio = $('#cambio');
	var cambio = $.trim($cambio.val());
	cambio = ($.isNumeric(cambio)) ? parseFloat(cambio) : 0;

	$('[data-estimado]').each(function (i) {
		costo = $.trim($(this).parent().find('[data-costo]').val());
		costo = ($.isNumeric(costo)) ? parseFloat(costo) : 0;
		factor = $.trim($(this).attr('data-factor'));
		factor = ($.isNumeric(factor)) ? parseFloat(factor) : 0;
		aumentado = costo * cambio;
		aumentado = aumentado.toFixed(2);
		estimado = costo * cambio * factor;
		estimado = estimado.toFixed(2);
		$(this).parent().find('[data-boliviano]').text(aumentado);
		$(this).parent().find('[data-bolivianos]').val(aumentado);
		$(this).parent().find('[data-estimados]').val(estimado);
		$(this).text(estimado);
	});
}
</script>
<?php require_once show_template('footer-advanced'); ?>