<?php

// Obtiene la fecha
$fecha = str_replace('/', '-', now($_institution['formato']));

// Obtiene los formatos
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Verifica si existe el parametro
if (sizeof($params) == 1) {
	// Verifica el tipo del parametro
	if (!is_date($params[0])) {
		// Redirecciona la pagina
		redirect('?/movimientos/cerrar/' . $fecha);
	}
} else {
	// Redirecciona la pagina
	redirect('?/movimientos/cerrar/' . $fecha);
}

// Obtiene el parametro
$fecha = date_encode($params[0]);

// Obtiene las ventas
$ventas = $db->query("select v.*, sum(v.cantidad) as numero, sum(v.importe) as subtotal from (select v.id_detalle, v.precio, v.cantidad, v.producto_id, p.nombre, p.codigo, (v.cantidad * v.precio) as importe from (select d.* from inv_egresos_detalles d left join inv_egresos e on d.egreso_id = e.id_egreso where e.tipo = 'venta' and e.fecha_egreso = '$fecha' and e.empleado_id = '" . $_user['persona_id'] . "') v left join inv_productos p on v.producto_id = p.id_producto) v group by v.producto_id order by v.codigo")->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los ingresos
$ingresos = $db->select("m.*, concat(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado")->from('caj_movimientos m')->join('sys_empleados e', 'm.empleado_id = e.id_empleado', 'left')->where('m.tipo', 'i')->where('m.empleado_id', $_user['persona_id'])->where('m.fecha_movimiento', $fecha)->order_by('m.fecha_movimiento desc, m.hora_movimiento desc')->fetch();

// Obtiene los egresos
$egresos = $db->select("m.*, concat(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado")->from('caj_movimientos m')->join('sys_empleados e', 'm.empleado_id = e.id_empleado', 'left')->where('m.tipo', 'e')->where('m.empleado_id', $_user['persona_id'])->where('m.fecha_movimiento', $fecha)->order_by('m.fecha_movimiento desc, m.hora_movimiento desc')->fetch();

// Obtiene los gastos
$gastos = $db->select("m.*, concat(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado")->from('caj_movimientos m')->join('sys_empleados e', 'm.empleado_id = e.id_empleado', 'left')->where('m.tipo', 'g')->where('m.empleado_id', $_user['persona_id'])->where('m.fecha_movimiento', $fecha)->order_by('m.fecha_movimiento desc, m.hora_movimiento desc')->fetch();

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title" data-header="true">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Reporte de ventas por producto</strong>
	</h3>
</div>
<div class="panel-body" data-servidor="<?= ip_local . name_project . '/diario.php'; ?>">
	<div class="row">
		<div class="col-xs-6">
			<div class="text-label hidden-xs">Seleccionar acción:</div>
			<div class="text-label visible-xs-block">Acciones:</div>
		</div>
		<div class="col-xs-6 text-right">
			<button class="btn btn-default" onclick="imprimir_diario()"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Imprimir</span></button>
			<div class="btn-group">
				<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
					<span class="glyphicon glyphicon-menu-hamburger"></span>
					<span class="hidden-xs">Acciones</span>
				</button>
				<ul class="dropdown-menu dropdown-menu-right">
					<li class="dropdown-header visible-xs-block">Seleccionar acción</li>
					<li><a href="#" data-toggle="modal" data-target="#modal_cambiar" data-backdrop="static" data-keyboard="false"><span class="glyphicon glyphicon-calendar"></span> Cambiar fecha</a></li>
				</ul>
			</div>
		</div>
	</div>
	<hr>
	<div class="well">
		<p class="lead margin-none">
			<b>Empleado:</b>
			<span><?= escape($_user['nombres'] . ' ' . $_user['paterno'] . ' ' . $_user['materno']); ?></span>
		</p>
	</div>
	<div class="row">
		<div class="col-sm-3">
			<p class="lead"><b><a href="?/movimientos/ingresos_listar" class="text-success">Ingresos</a></b></p>
			<?php if ($ingresos) : ?>
			<ul class="list-group">
				<?php $total_ingreso = 0; ?>
				<?php foreach ($ingresos as $nro => $ingreso) : ?>
				<?php $total_ingreso = $total_ingreso + $ingreso['monto']; ?>
				<li class="list-group-item">
					<span><?= $ingreso['concepto']; ?></span>
					<strong class="pull-right"><?= $ingreso['monto']; ?></strong>
				</li>
				<?php endforeach ?>
				<li class="list-group-item active">
					<strong>Total</strong>
					<strong class="pull-right"><?= number_format($total_ingreso, 2, '.', ''); ?></strong>
				</li>
			</ul>
			<?php else : ?>
			<div class="well">No hay ingresos</div>
			<?php endif ?>
		</div>
		<div class="col-sm-3">
			<p class="lead"><b><a href="?/movimientos/egresos_listar" class="text-danger">Egresos</a></b></p>
			<?php if ($egresos) : ?>
			<ul class="list-group">
				<?php $total_egreso = 0; ?>
				<?php foreach ($egresos as $nro => $egreso) : ?>
				<?php $total_egreso = $total_egreso + $egreso['monto']; ?>
				<li class="list-group-item">
					<span><?= $egreso['concepto']; ?></span>
					<strong class="pull-right"><?= $egreso['monto']; ?></strong>
				</li>
				<?php endforeach ?>
				<li class="list-group-item active">
					<strong>Total</strong>
					<strong class="pull-right"><?= number_format($total_egreso, 2, '.', ''); ?></strong>
				</li>
			</ul>
			<?php else : ?>
			<div class="well">No hay egresos</div>
			<?php endif ?>
		</div>
		<div class="col-sm-3">
			<p class="lead"><b><a href="?/movimientos/gastos_listar" class="text-danger">Gastos</a></b></p>
			<?php if ($gastos) : ?>
			<ul class="list-group">
				<?php $total_gasto = 0; ?>
				<?php foreach ($gastos as $nro => $gasto) : ?>
				<?php $total_gasto = $total_gasto + $gasto['monto']; ?>
				<li class="list-group-item">
					<span><?= $gasto['concepto']; ?></span>
					<strong class="pull-right"><?= $gasto['monto']; ?></strong>
				</li>
				<?php endforeach ?>
				<li class="list-group-item active">
					<strong>Total</strong>
					<strong class="pull-right"><?= number_format($total_gasto, 2, '.', ''); ?></strong>
				</li>
			</ul>
			<?php else : ?>
			<div class="well">No hay gastos</div>
			<?php endif ?>
		</div>
		<div class="col-sm-3">
			<p class="lead"><b><a href="?/reportes/diario" class="text-success">Ventas</a></b></p>
			<?php if ($ventas) : ?>
			<ul class="list-group">
				<?php $total_venta = 0; ?>
				<?php foreach ($ventas as $nro => $venta) : ?>
				<?php $total_venta = $total_venta + $venta['subtotal']; ?>
				<li class="list-group-item">
					<span>
						<samp><?= $venta['codigo']; ?></samp>
						<small><em><?= $venta['nombre']; ?></em></small>
						<small><strong>(<?= $venta['numero']; ?>)</strong></small>
					</span>
					<strong class="pull-right"><?= $venta['subtotal']; ?></strong>
				</li>
				<?php endforeach ?>
				<li class="list-group-item active">
					<strong>Total</strong>
					<strong class="pull-right"><?= number_format($total_venta, 2, '.', ''); ?></strong>
				</li>
			</ul>
			<?php else : ?>
			<div class="well">No hay ventas</div>
			<?php endif ?>
		</div>
	</div>
	<div class="well">
		<p class="lead margin-none">
			<b>Total:</b>
			<u id="total"><?= number_format($total_ingreso - $total_egreso - $total_gasto + $total_venta, 2, '.', ''); ?></u>
			<span><?= escape($moneda); ?></span>
		</p>
		<p class="margin-none">
			<em>El total corresponde a la siguiente fórmula:</em>
			<samp><b>Ingresos - Egresos - Gastos + Ventas</b></samp>
		</p>
	</div>
</div>

<!-- Modal cambiar inicio -->
<div id="modal_cambiar" class="modal fade" tabindex="-1">
	<div class="modal-dialog">
		<form method="post" action="?/movimientos/cerrar" id="form_cambiar" class="modal-content loader-wrapper" autocomplete="off">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Cambiar fecha</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label for="fecha_cambiar" class="control-label">Fecha:</label>
					<input type="text" value="<?= date_decode($fecha, $_institution['formato']); ?>" name="fecha" id="fecha_cambiar" class="form-control" data-validation="required date" data-validation-format="<?= $formato_textual; ?>">
				</div>
			</div>
			<div class="modal-footer">
				<button type="submit" class="btn btn-primary">
					<span class="glyphicon glyphicon-share-alt"></span>
					<span>Cambiar</span>
				</button>
				<button type="reset" class="btn btn-default">
					<span class="glyphicon glyphicon-refresh"></span>
					<span>Restablecer</span>
				</button>
			</div>
			<div id="loader_cambiar" class="loader-wrapper-backdrop hidden">
				<span class="loader"></span>
			</div>
		</form>
	</div>
</div>
<!-- Modal cambiar fin -->

<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
<script src="<?= js; ?>/bootstrap-notify.min.js"></script>
<script>
$(function () {
	var $modal_cambiar = $('#modal_cambiar'), $form_cambiar = $('#form_cambiar'), $loader_cambiar = $('#loader_cambiar'), $fecha_cambiar = $('#fecha_cambiar');

	$.validate({
		form: '#form_cambiar',
		modules: 'date',
		onSuccess: function () {
			$loader_cambiar.removeClass('hidden');
			var direccion_cambiar = $.trim($form_cambiar.attr('action')), fecha_cambiar = $.trim($fecha_cambiar.val());
			fecha_cambiar = fecha_cambiar.replace(new RegExp('/', 'g'), '-');
			window.location = direccion_cambiar + '/' + fecha_cambiar;
		}
	});

	$fecha_cambiar.datetimepicker({
		format: '<?= strtoupper($formato_textual); ?>'
	});

	$form_cambiar.on('submit', function (e) {
		e.preventDefault();
	});

	$modal_cambiar.on('hidden.bs.modal', function () {
		$form_cambiar.trigger('reset');
	}).on('show.bs.modal', function (e) {
		if ($('.modal:visible').size() != 0) { e.preventDefault(); }
	});

	$('#table').DataFilter({
		name: 'asistencias',
		reports: 'excel|word|pdf|html',
		values: {
			stateSave: true
		}
	});
});

function imprimir_diario() {
	var servidor = $.trim($('[data-servidor]').attr('data-servidor'));

	$.ajax({
		type: 'post',
		dataType: 'json',
		url: '?/movimientos/obtener',
		data: {
			fecha: '<?= $fecha; ?>'
		}
	}).done(function (respuesta) {
		$.ajax({
			type: 'post',
			dataType: 'json',
			url: servidor,
			data: respuesta
		}).done(function (respuesta) {
			switch (respuesta.estado) {
				case 'success':
					$.notify({
						message: 'Imprimiendo reporte diario.'
					}, {
						type: 'success'
					});
					break;
				default:
					$.notify({
						message: 'La impresora no responde, asegurese de que este conectada y vuelva a intentarlo nuevamente.'
					}, {
						type: 'danger'
					});
					break;
			}
		}).fail(function () {
			$.notify({
				message: 'Ocurrió un problema en el envío de la información, vuelva a intentarlo nuevamente y si persiste el problema contactese con el administrador.'
			}, {
				type: 'danger'
			});
		});
	}).fail(function () {
		$.notify({
			message: 'Ocurrió un problema en el envío de la información, vuelva a intentarlo nuevamente y si persiste el problema contactese con el administrador.'
		}, {
			type: 'danger'
		});
	});
}
</script>
<?php require_once show_template('footer-advanced'); ?>