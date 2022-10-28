<?php

// Obtiene las fechas inicial y final
$fecha_inicial = str_replace('/', '-', now($_institution['formato']));
$fecha_final = str_replace('/', '-', now($_institution['formato']));

// Obtiene los formatos
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Verifica si existen los parametros
if (sizeof($params) == 2) {
	// Verifica el tipo de los parametros
	if (!is_date($params[0]) || !is_date($params[1])) {
		// Redirecciona la pagina
		redirect('?/movimientos/gastos_listar/' . $fecha_inicial . '/' . $fecha_final);
	}
} else {
	// Redirecciona la pagina
	redirect('?/movimientos/gastos_listar/' . $fecha_inicial . '/' . $fecha_final);
}

// Obtiene los parametros
$fecha_inicial = date_encode($params[0]);
$fecha_final = date_encode($params[1]);

// Obtiene los gastos
$gastos = $db->select("m.*, concat(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado")->from('caj_movimientos m')->join('sys_empleados e', 'm.empleado_id = e.id_empleado', 'left')->where('m.tipo', 'g')->where('m.empleado_id', $_user['persona_id'])->between('m.fecha_movimiento', $fecha_inicial, $fecha_final)->order_by('m.fecha_movimiento desc, m.hora_movimiento desc')->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('gastos_crear', $permisos);
$permiso_modificar = in_array('gastos_modificar', $permisos);
$permiso_eliminar = in_array('gastos_eliminar', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title" data-header="true">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Gastos de efectivo</strong>
	</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-xs-6">
			<div class="text-label hidden-xs">Seleccionar acción:</div>
			<div class="text-label visible-xs-block">Acciones:</div>
		</div>
		<div class="col-xs-6 text-right">
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
	<?php if (isset($_SESSION[temporary])) : ?>
	<div class="alert alert-<?= $_SESSION[temporary]['alert']; ?>">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong><?= $_SESSION[temporary]['title']; ?></strong>
		<p><?= $_SESSION[temporary]['message']; ?></p>
	</div>
	<?php unset($_SESSION[temporary]); ?>
	<?php endif ?>
	<?php if ($gastos) : ?>
	<table id="table" class="table table-bordered table-condensed table-striped table-hover">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
				<th class="text-nowrap">Comprobante</th>
				<th class="text-nowrap">Fecha</th>
				<th class="text-nowrap">Concepto</th>
				<th class="text-nowrap">Monto <?= escape($moneda); ?></th>
				<th class="text-nowrap">Observación</th>
				<th class="text-nowrap">Empleado</th>
				<?php if ($permiso_modificar || $permiso_eliminar) : ?>
				<th class="text-nowrap">Opciones</th>
				<?php endif ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Comprobante</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Concepto</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Monto <?= escape($moneda); ?></th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Observación</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Empleado</th>
				<?php if ($permiso_modificar || $permiso_eliminar) : ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php endif ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($gastos as $nro => $gasto) : ?>
			<tr>
				<th class="text-nowrap"><?= $nro + 1; ?></th>
				<td class="text-nowrap"><?= escape($gasto['nro_comprobante']); ?></td>
				<td class="text-nowrap">
					<span><?= escape(date_decode($gasto['fecha_movimiento'], $_institution['formato'])); ?></span>
					<span class="text-primary"><?= escape($gasto['hora_movimiento']); ?></span>
				</td>
				<td><?= escape($gasto['concepto']); ?></td>
				<td class="text-nowrap text-right" data-monto="<?= $gasto['monto']; ?>"><?= escape($gasto['monto']); ?></td>
				<td><?= escape($gasto['observacion']); ?></td>
				<td class="text-nowrap"><?= escape($gasto['empleado']); ?></td>
				<?php if ($permiso_modificar || $permiso_eliminar) : ?>
				<td class="text-nowrap">
					<?php if ($permiso_modificar) : ?>	
					<a href="?/movimientos/gastos_modificar/<?= $gasto['id_movimiento']; ?>" data-toggle="tooltip" data-title="Modificar gasto"><i class="glyphicon glyphicon-edit"></i></a>
					<?php endif ?>
					<?php if ($permiso_eliminar) : ?>	
					<a href="?/movimientos/gastos_eliminar/<?= $gasto['id_movimiento']; ?>" data-toggle="tooltip" data-title="Eliminar gasto" data-eliminar="true"><i class="glyphicon glyphicon-trash"></i></a>
					<?php endif ?>
				</td>
				<?php endif ?>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
	<div class="well">
		<p class="lead margin-none">
			<b>Empleado:</b>
			<span><?= escape($_user['nombres'] . ' ' . $_user['paterno'] . ' ' . $_user['materno']); ?></span>
		</p>
		<p class="lead margin-none">
			<b>Total:</b>
			<u id="monto">0.00</u>
			<span><?= escape($moneda); ?></span>
		</p>
	</div>
	<?php else : ?>
	<div class="alert alert-info margin-none">
		<strong>Atención!</strong>
		<ul>
			<li>No existen gastos registrados.</li>
			<li>Puede buscar gastos por rango de fechas, verifique que las fechas sean válidas.</li>
		</ul>
	</div>
	<?php endif ?>
</div>

<!-- Modal cambiar inicio -->
<div id="modal_cambiar" class="modal fade" tabindex="-1">
	<div class="modal-dialog">
		<form method="post" action="?/movimientos/gastos_listar" id="form_cambiar" class="modal-content loader-wrapper" autocomplete="off">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Cambiar fecha</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label for="inicial_cambiar" class="control-label">Fecha inicial:</label>
					<input type="text" value="<?= date_decode($fecha_inicial, $_institution['formato']); ?>" name="inicial" id="inicial_cambiar" class="form-control" data-validation="required date" data-validation-format="<?= $formato_textual; ?>">
				</div>
				<div class="form-group">
					<label for="final_cambiar" class="control-label">Fecha final:</label>
					<input type="text" value="<?= date_decode($fecha_final, $_institution['formato']); ?>" name="final" id="final_cambiar" class="form-control" data-validation="required date" data-validation-format="<?= $formato_textual; ?>">
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
<script>
$(function () {
	<?php if ($permiso_eliminar) : ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Está seguro que desea eliminar el gasto?', function (result) {
			if(result){
				window.location = url;
			}
		});
	});
	<?php endif ?>
	
	<?php if ($permiso_crear) : ?>
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'n':
					e.preventDefault();
					window.location = '?/movimientos/gastos_crear';
				break;
			}
		}
	});
	<?php endif ?>

	var $modal_cambiar = $('#modal_cambiar'), $form_cambiar = $('#form_cambiar'), $loader_cambiar = $('#loader_cambiar'), $inicial_cambiar = $('#inicial_cambiar'), $final_cambiar = $('#final_cambiar');

	$.validate({
		form: '#form_cambiar',
		modules: 'date',
		onSuccess: function () {
			$loader_cambiar.removeClass('hidden');
			var direccion_cambiar = $.trim($form_cambiar.attr('action')), inicial_cambiar = $.trim($inicial_cambiar.val()), final_cambiar = $.trim($final_cambiar.val());
			inicial_cambiar = inicial_cambiar.replace(new RegExp('/', 'g'), '-');
			final_cambiar = final_cambiar.replace(new RegExp('/', 'g'), '-');
			window.location = direccion_cambiar + '/' + inicial_cambiar + '/' + final_cambiar;
		}
	});

	$inicial_cambiar.datetimepicker({
		format: '<?= strtoupper($formato_textual); ?>'
	});

	$final_cambiar.datetimepicker({
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

	$('#table').on('search.dt order.dt page.dt length.dt', function () {
		var suma = 0;
		$('[data-monto]:visible').each(function (i) {
			var monto = parseFloat($(this).attr('data-monto'));
			suma = suma + monto;
		});
		$('#monto').text(suma.toFixed(2));
	}).DataFilter({
		name: 'movimientos',
		reports: 'excel|word|pdf|html',
		values: {
			stateSave: true
		}
	});
});
</script>
<?php require_once show_template('footer-advanced'); ?>