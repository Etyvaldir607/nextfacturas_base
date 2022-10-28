<?php
//$id_egreso_especial = (isset($params[0])) ? $params[0] : 0;


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

// Obtiene los egresos
if( isset($params[0]) || isset($params[1]) ){
    $egresos = $db->query("select i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno
                          from inv_egresos i
                          left join inv_almacenes a ON i.almacen_id = a.id_almacen
                          left join sys_empleados e ON i.empleado_id = e.id_empleado 
                          where (i.tipo = 'Baja' OR i.tipo = 'Egreso especial') AND 
                                i.fecha_egreso >= '".$fecha_inicial."' AND
                                i.fecha_egreso <= '".$fecha_final."'
                          order by i.id_egreso desc")
                          ->fetch();
                          
}else{
    $egresos = $db->query("select i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno
                          from inv_egresos i
                          left join inv_almacenes a ON i.almacen_id = a.id_almacen
                          left join sys_empleados e ON i.empleado_id = e.id_empleado 
                          where (i.tipo = 'Baja' OR i.tipo = 'Egreso especial')
                          order by i.id_egreso desc
                          LIMIT 50")
                          ->fetch();
}                         

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los almacenes
$almacenes = $db->from('inv_almacenes')->fetch();

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);
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
		<b>Listado de egresos</b>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_cambiar || $permiso_crear || $permiso_imprimir) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para realizar un egreso hacer clic en el siguiente botón: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_cambiar) { ?>
			<button class="btn btn-default" data-cambiar="true">
				<span class="glyphicon glyphicon-calendar"></span>
				<span class="hidden-xs">Cambiar</span>
			</button>
			<?php } ?>
			<?php if ($permiso_imprimir) { ?>
			<a href="?/egresos/imprimir" target="_blank" class="btn btn-info">
				<span class="glyphicon glyphicon-print"></span>
				<span class="hidden-xs">Imprimir</span>
			</a>
			<?php } ?>
			<?php if ($permiso_crear) { ?>
			<div class="btn-group">
				<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
					<span class="glyphicon glyphicon-plus"></span>
					<span>Egresar</span>
				</button>
				<ul class="dropdown-menu dropdown-menu-right">
					<?php foreach ($almacenes as $elemento) { ?>
					<li><a href="?/egresos/crear/<?= $elemento['id_almacen']; ?>"><span class="glyphicon glyphicon-star"></span> <?= escape($elemento['almacen'] . (($elemento['principal'] == 'S') ? ' (principal)' : (($elemento['especial']=='si') ? ' (especial)' : ''))); ?></a></li>
					<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<?php if ($egresos) { ?>
	<table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
				<th class="text-nowrap">Fecha</th>
				<th class="text-nowrap">Almacén</th>
				<th class="text-nowrap">Tipo</th>
				<th class="text-nowrap">Descripción</th>
				<th class="text-nowrap">Monto total <?= escape($moneda); ?></th>
				<th class="text-nowrap">Registros</th>
				<th class="text-nowrap">Empleado</th>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
				<th class="text-nowrap">Opciones</th>
				<?php } ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Almacén</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Tipo</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Descripción</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Monto total <?= escape($moneda); ?></th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Registros</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Empleado</th>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($egresos as $nro => $egreso) { ?>
			<tr>
				<th class="text-nowrap"><?= $egreso['nro_movimiento']; ?></th>
				<td class="text-nowrap">
				    <?= escape(date_decode($egreso['fecha_egreso'], $_institution['formato'])); ?> 
				    <small class="text-success"><?= escape($egreso['hora_egreso']); ?></small>
				</td>
				<td class="text-nowrap <?= ($egreso['principal'] == 'S') ? 'info' : ''; ?>"><?= escape($egreso['almacen']); ?></td>
				<td class="text-nowrap"><?= escape($egreso['tipo']); ?></td>
				<td class="width-md"><?= escape($egreso['descripcion_venta']); ?></td>
				<td class="text-nowrap text-right"><?= escape(number_format($egreso['monto_total'],2,',','.')); ?></td>
				<td class="text-nowrap text-right"><?= escape($egreso['nro_registros']); ?></td>
				<td class="width-md"><?= escape($egreso['nombres'] . ' ' . $egreso['paterno'] . ' ' . $egreso['materno']); ?></td>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
				<td class="text-nowrap">
					<?php if ($permiso_ver) { ?>
					<a href="?/egresos/ver/<?= $egreso['id_egreso']; ?>" data-toggle="tooltip" data-title="Ver detalle"><span class="glyphicon glyphicon-list-alt"></span></a>
					<?php } ?>
					<?php if ($permiso_editar10) { ?>
					<a href="?/egresos/editar/<?= $egreso['id_egreso']; ?>" data-toggle="tooltip" data-title="Editar egreso"><span class="glyphicon glyphicon-edit"></span></a>
					<?php } ?>
					<?php if ($permiso_eliminar10) { ?>
					<a href="?/egresos/eliminar/<?= $egreso['id_egreso']; ?>" data-toggle="tooltip" data-title="Eliminar egreso" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span></a>
					<?php } ?>
				</td>
				<?php } ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen egresos registrados en la base de datos.</p>
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
<script src="<?= js; ?>/dataFiltersCustom.min.js"></script>
<script src="<?= js; ?>/moment.min.js"></script>
<script src="<?= js; ?>/moment.es.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
<script>
$(function () {
    
    // if(<?= $id_egreso_especial;?> != ''){
    //     window.open('?/egresos/imprimir_nota/' + <?php echo $id_egreso_especial;?>, true);
    //     window.location = '?/egresos/listar';
    // }
    
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

	<?php if ($permiso_cambiar) { ?>
	var formato = $('[data-formato]').attr('data-formato');
	var mascara = $('[data-mascara]').attr('data-mascara');
	var gestion = $('[data-gestion]').attr('data-gestion');
	var $inicial_fecha = $('#inicial_fecha');
	var $final_fecha = $('#final_fecha');

	$.validate({
		form: '#form_fecha',
		modules: 'date',
		onSuccess: function () {
			var inicial_fecha = $.trim($('#inicial_fecha').val());
			var final_fecha = $.trim($('#final_fecha').val());
			var vacio = gestion.replace(new RegExp('9', 'g'), '0');

			inicial_fecha = inicial_fecha.replace(new RegExp('\\.', 'g'), '-');
			inicial_fecha = inicial_fecha.replace(new RegExp('/', 'g'), '-');
			final_fecha = final_fecha.replace(new RegExp('\\.', 'g'), '-');
			final_fecha = final_fecha.replace(new RegExp('/', 'g'), '-');
			vacio = vacio.replace(new RegExp('\\.', 'g'), '-');
			vacio = vacio.replace(new RegExp('/', 'g'), '-');
			final_fecha = (final_fecha != '') ? ('/' + final_fecha ) : '';
			inicial_fecha = (inicial_fecha != '') ? ('/' + inicial_fecha) : ((final_fecha != '') ? ('/' + vacio) : ''); 
			
			window.location = '?/egresos/listar' + inicial_fecha + final_fecha;
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

	$inicial_fecha.on('dp.change', function (e) {
		$final_fecha.data('DateTimePicker').minDate(e.date);
	});
	
	$final_fecha.on('dp.change', function (e) {
		$inicial_fecha.data('DateTimePicker').maxDate(e.date);
	});

	var $form_fecha = $('#form_fecha');
	var $modal_fecha = $('#modal_fecha');

	$form_fecha.on('submit', function (e) {
		e.preventDefault();
	});

	$modal_fecha.on('show.bs.modal', function () {
		$form_fecha.trigger('reset');
	});

	$modal_fecha.on('shown.bs.modal', function () {
		$modal_fecha.find('[data-aceptar]').focus();
	});

	$modal_fecha.find('[data-cancelar]').on('click', function () {
		$modal_fecha.modal('hide');
	});

	$modal_fecha.find('[data-aceptar]').on('click', function () {
		$form_fecha.submit();
	});

	$('[data-cambiar]').on('click', function () {
		$('#modal_fecha').modal({
			backdrop: 'static'
		});
	});
	<?php } ?>
	
	<?php if ($egresos) { ?>
	var table = $('#table').DataFilter({
		filter: true,
		name: 'Reporte de egresos',
		fechas: 'Reporte generado para las fechas: desde <?= $fecha_inicial ?> hasta <?= ($fecha_final == $gestion_limite)? date('Y-m-d') : $fecha_final ?> ',
        creacion: "Fecha y hora de Impresion: <?= date("d/m/Y H:i:s") ?>",
        total: 6,
        reports: 'excel|pdf',
        values: {
            order: [[0, 'desc']]
        }
	});

	$('#states_0').find(':radio[value="hide"]').trigger('click');
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced'); ?>