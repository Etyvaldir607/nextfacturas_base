<?php

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Obtiene el rango de fechas
$gestion = date('Y');
$gestion_base = date('Y-m-d');
//$gestion_base = ($gestion - 16) . date('-m-d');
$gestion_limite = ($gestion + 16) . date('-m-d');

// Obtiene fecha inicial
$fecha_inicial = (isset($params[0])) ? $params[0] : $gestion_base;
$fecha_inicial = (is_date($fecha_inicial)) ? $fecha_inicial : $gestion_base;
$fecha_inicial = date_encode($fecha_inicial);

// Obtiene fecha final
$fecha_final = (isset($params[1])) ? $params[1] : $gestion_limite;
$fecha_final = (is_date($fecha_final)) ? $fecha_final : $gestion_limite;
$fecha_final = date_encode($fecha_final);

$fecha_final_excel = (isset($params[1])) ? $params[1] : date('Y-m-d');

$IdUsuario=$_user['id_user'];
// Obtiene las ventas
$ventas = $db->query("SELECT i.*,a.almacen,a.principal,e.nombres,e.paterno,e.materno,IFNULL(
						(SELECT true FROM sys_supervisor AS s WHERE s.user_ids='{$IdUsuario}' AND s.user_id=u.id_user)
					,true)AS sub
					FROM inv_egresos i
					LEFT JOIN inv_almacenes a ON i.almacen_id=a.id_almacen
					LEFT JOIN sys_empleados e ON i.empleado_id=e.id_empleado
					LEFT JOIN sys_users AS u ON e.id_empleado=u.persona_id
					WHERE i.tipo='Venta' AND i.codigo_control!='' AND i.fecha_egreso>='{$fecha_inicial}' AND i.fecha_egreso<='{$fecha_final}'
					ORDER BY i.fecha_egreso DESC,i.hora_egreso DESC")->fetch();

//echo $db->last_query();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_ver = in_array('facturas_ver', $permisos);
$permiso_imprimir = in_array('facturas_imprimir', $permisos);
$permiso_devolucion = in_array('facturas_devolucion', $permisos);
$permiso_cambiar = true;

?>
<?php require_once show_template('header-advanced'); ?>
<style>
	.table-xs tbody {
		font-size: 12px;
	}

	.width-sm {
		min-width: 150px;
	}

	.width-md {
		min-width: 200px;
	}

	.width-lg {
		min-width: 250px;
	}
</style>
<div class="panel-heading" data-formato="<?= strtoupper($formato_textual); ?>" data-mascara="<?= $formato_numeral; ?>" data-gestion="<?= date_decode($gestion_base, $_institution['formato']); ?>">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Lista de todas las facturas</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_cambiar || $permiso_imprimir) { ?>
		<div class="row">
			<div class="col-sm-8 hidden-xs">
				<div class="text-label">Para realizar una venta hacer clic en el siguiente botón: </div>
			</div>
			<div class="col-xs-12 col-sm-4 text-right">
				<?php if ($permiso_cambiar) { ?>
					<button class="btn btn-default" data-cambiar="true"><i class="glyphicon glyphicon-calendar"></i><span class="hidden-xs"> Cambiar</span></button>
				<?php } ?>
				<?php if ($permiso_imprimir10) { ?>
					<a href="?/operaciones/facturas_imprimir" class="btn btn-info" target="_blank"><i class="glyphicon glyphicon-print"></i><span class="hidden-xs"> Imprimir</span></a>
				<?php } ?>
			</div>
		</div>
		<hr>
	<?php } ?>
	<?php if ($ventas) { ?>
		<table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
			<thead>
				<tr class="active">
					<th class="text-nowrap">Nro Nota</th>
					<th class="text-nowrap">Nro Factura</th>
					<th class="text-nowrap">Fecha</th>
					<th class="text-nowrap">Cliente</th>
					<th class="text-nowrap">NIT/CI</th>
					<th class="text-nowrap">Codigo</th>
					<th class="text-nowrap">Monto total <?= escape($moneda); ?></th>
					<th class="text-nowrap">Registros</th>
					<th class="text-nowrap">Almacen</th>
					<th class="text-nowrap">Empleado</th>
					<?php if ($permiso_ver) { ?>
						<th class="text-nowrap">Opciones</th>
					<?php } ?>
				</tr>
			</thead>
			<tfoot>
				<tr class="active">
					<th class="text-nowrap text-middle" data-datafilter-filter="true">Nro Nota</th>
					<th class="text-nowrap text-middle" data-datafilter-filter="true">Nro Factura</th>
					<th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha</th>
					<th class="text-nowrap text-middle" data-datafilter-filter="true">Cliente</th>
					<th class="text-nowrap text-middle" data-datafilter-filter="true">NIT/CI</th>
					<th class="text-nowrap text-middle" data-datafilter-filter="true">Codigo</th>
					<th class="text-nowrap text-middle" data-datafilter-filter="true">Monto total <?= escape($moneda); ?></th>
					<th class="text-nowrap text-middle" data-datafilter-filter="true">Registros</th>
					<th class="text-nowrap text-middle" data-datafilter-filter="true">Almacen</th>
					<th class="text-nowrap text-middle" data-datafilter-filter="true">Empleado</th>
					<?php if ($permiso_ver || $permiso_devolucion) { ?>
						<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
					<?php } ?>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ($ventas as $nro => $venta) {
					if($venta['sub'] || $IdUsuario<=2):
				?>
					<tr>
						<td class="text-nowrap text-right"><?= escape($venta['nro_nota']); ?></td>
						<td class="text-nowrap text-right"><?= escape($venta['nro_factura']); ?></td>
						<td class="text-nowrap"><?= escape(date_decode($venta['fecha_egreso'], $_institution['formato'])); ?> <small class="text-success"><?= escape($venta['hora_egreso']); ?></small></td>
						<td class="text-nowrap"><?= escape($venta['nombre_cliente']); ?></td>
						<td class="text-nowrap"><?= escape($venta['nit_ci']); ?></td>
						<td class="text-nowrap"><?= escape($venta['codigo_control']); ?></td>
						<td class="text-nowrap text-right"><?= escape(number_format($venta['monto_total'],2 ,',','')); ?></td>
						<td class="text-nowrap text-right"><?= escape($venta['nro_registros']); ?></td>
						<td class="text-nowrap"><?= escape($venta['almacen']); ?></td>
						<td class="width-md"><?= escape($venta['nombres'] . ' ' . $venta['paterno'] . ' ' . $venta['materno']); ?></td>
						<td class="text-nowrap">
							<?php if ($permiso_ver) { ?>
								<a href="?/operaciones/facturas_ver/<?= $venta['id_egreso']; ?>" data-toggle="tooltip" data-title="Ver detalle de venta"><i class="glyphicon glyphicon-list-alt"></i></a>
							<?php } ?>
							<?php if ($permiso_devolucion) { ?>
								<a href="?/operaciones/facturas_devolucion/<?= $venta['id_egreso']; ?>" data-toggle="tooltip" data-title="Devolucion"><i class="glyphicon glyphicon-transfer"></i></a>
							<?php } ?>
						</td>
					</tr>
						<?php endif;} ?>
			</tbody>
		</table>
	<?php } else { ?>
		<div class="alert alert-danger">
			<strong>Advertencia!</strong>
			<p>No existen ventas electrónicas registrados en la base de datos.</p>
		</div>
	<?php } ?>
</div>

<!-- Inicio modal fecha -->
<?php if ($permiso_cambiar) { ?>
	<div id="modal_fecha" class="modal fade">
		<div class="modal-dialog">
			<form id="form_fecha" class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Cambiar fecha</h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-sm-12">
							<div class="form-group">
								<label for="inicial_fecha">Fecha inicial:</label>
								<input type="text" name="inicial" value="<?= ($fecha_inicial != $gestion_base) ? date_decode($fecha_inicial, $_institution['formato']) : ''; ?>" id="inicial_fecha" class="form-control" autocomplete="off" data-validation="date" data-validation-format="<?= $formato_textual; ?>" data-validation-optional="true">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<div class="form-group">
								<label for="final_fecha">Fecha final:</label>
								<input type="text" name="final" value="<?= ($fecha_final != $gestion_limite) ? date_decode($fecha_final, $_institution['formato']) : ''; ?>" id="final_fecha" class="form-control" autocomplete="off" data-validation="date" data-validation-format="<?= $formato_textual; ?>" data-validation-optional="true">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary" data-aceptar="true">
						<span class="glyphicon glyphicon-ok"></span>
						<span>Aceptar</span>
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
<!-- Fin modal fecha -->
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.maskedinput.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<!--script src="<?= js; ?>/jquery.dataFilters.min.js"></script-->
<script src="<?= js; ?>/dataFiltersCustom.min.js"></script>
<script src="<?= js; ?>/moment.min.js"></script>
<script src="<?= js; ?>/moment.es.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
<script>
	$(function() {

		<?php if (isset($_SESSION['imprimir'])) { ?>
			// window.location = '?/operaciones/notas_crear/<?= $_SESSION['imprimir'] ?>';
			$.open('?/notas/imprimir/<?= $_SESSION['imprimir'] ?>', true);
			// window.location.reload();
			<?php unset($_SESSION['imprimir']); ?>
		<?php } ?>


		<?php if ($permiso_cambiar) { ?>
			var formato = $('[data-formato]').attr('data-formato');
			var mascara = $('[data-mascara]').attr('data-mascara');
			var gestion = $('[data-gestion]').attr('data-gestion');
			var $inicial_fecha = $('#inicial_fecha');
			var $final_fecha = $('#final_fecha');

			$.validate({
				form: '#form_fecha',
				modules: 'date',
				onSuccess: function() {
					var inicial_fecha = $.trim($('#inicial_fecha').val());
					var final_fecha = $.trim($('#final_fecha').val());
					var vacio = gestion.replace(new RegExp('9', 'g'), '0');
					inicial_fecha = inicial_fecha.replace(new RegExp('\\.', 'g'), '-');
					inicial_fecha = inicial_fecha.replace(new RegExp('/', 'g'), '-');
					final_fecha = final_fecha.replace(new RegExp('\\.', 'g'), '-');
					final_fecha = final_fecha.replace(new RegExp('/', 'g'), '-');
					vacio = vacio.replace(new RegExp('\\.', 'g'), '-');
					vacio = vacio.replace(new RegExp('/', 'g'), '-');
					final_fecha = (final_fecha != '') ? ('/' + final_fecha) : '';
					inicial_fecha = (inicial_fecha != '') ? ('/' + inicial_fecha) : ((final_fecha != '') ? ('/' + vacio) : '');
					window.location = '?/operaciones/facturas_listar' + inicial_fecha + final_fecha;
				}
			});

			//$inicial_fecha.mask(mascara).datetimepicker({
			$inicial_fecha.datetimepicker({
				format: formato
			});

			//$final_fecha.mask(mascara).datetimepicker({
			$final_fecha.datetimepicker({
				format: formato
			});

			$inicial_fecha.on('dp.change', function(e) {
				$final_fecha.data('DateTimePicker').minDate(e.date);
			});

			$final_fecha.on('dp.change', function(e) {
				$inicial_fecha.data('DateTimePicker').maxDate(e.date);
			});

			var $form_fecha = $('#form_fecha');
			var $modal_fecha = $('#modal_fecha');

			$form_fecha.on('submit', function(e) {
				e.preventDefault();
			});

			$modal_fecha.on('show.bs.modal', function() {
				$form_fecha.trigger('reset');
			});

			$modal_fecha.on('shown.bs.modal', function() {
				$modal_fecha.find('[data-aceptar]').focus();
			});

			$modal_fecha.find('[data-cancelar]').on('click', function() {
				$modal_fecha.modal('hide');
			});

			$modal_fecha.find('[data-aceptar]').on('click', function() {
				$form_fecha.submit();
			});

			$('[data-cambiar]').on('click', function() {
				$('#modal_fecha').modal({
					backdrop: 'static'
				});
			});
		<?php } ?>

		<?php if ($ventas) { ?>
			var table = $('#table').DataFilter({
				filter: true,
				name: 'facturas',
				fechas: 'Desde <?= $fecha_inicial ?> hasta <?= $fecha_final_excel ?> ',
		        creacion: "Fecha y hora de Impresion: <?= date("d/m/Y H:i:s") ?>",
		        total: '6',		
				reports: 'excel|pdf'
			});
		<?php } ?>
	});
</script>
<?php require_once show_template('footer-advanced'); ?>