<?php
// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Obtiene las ventas
if(true/*$_user['rol'] == 'Superusuario'*/){

$proformas = $db->query("SELECT DISTINCT(i.nro_movimiento), i.*,c.codigo,a.almacen,a.principal,e.nombres,e.paterno,e.materno,e.cargo,IFNULL(
                        (SELECT true FROM sys_supervisor AS s WHERE s.user_ids='{$IdUsuario}' AND s.user_id=u.id_user)
                    ,true)AS sub, ac.estado_pedido, ac.estado as estado_a, ac.id_asignacion_cliente, CONCAT(em.nombres,' ',em.paterno,' ',em.materno) as distribuidor, i.cobrar
                FROM inv_egresos i
                LEFT JOIN inv_almacenes a ON i.almacen_id=a.id_almacen
                LEFT JOIN sys_empleados e ON i.empleado_id=e.id_empleado
                LEFT JOIN sys_users AS u ON e.id_empleado=u.persona_id
                LEFT JOIN inv_clientes c ON i.cliente_id = c.id_cliente
                LEFT JOIN inv_asignaciones_clientes ac ON i.id_egreso = ac.egreso_id AND ac.estado = 'A'
                LEFT JOIN sys_empleados em ON ac.distribuidor_id=em.id_empleado
                WHERE i.tipo IN('Preventa', 'No venta')
				        AND preventa is NULL OR (preventa='habilitado' AND estadoe!=3)
				ORDER BY i.fecha_egreso DESC, i.hora_egreso DESC
				")->fetch();
				
				// WHERE (i.estadoe>2 OR i.estadoe<=4)				
				// AND i.tipo IN('Preventa', 'No venta')
				// AND (i.preventa != 'eliminado' AND i.preventa != 'anulado' OR i.preventa <=> NULL)
				
} else {
    $empleado_id = $_user['persona_id'];
	$proformas = $db->query("SELECT DISTINCT(i.nro_movimiento), i.*,c.codigo,a.almacen,a.principal,e.nombres,e.paterno,e.materno,e.cargo,IFNULL(
                        (SELECT true FROM sys_supervisor AS s WHERE s.user_ids='{$IdUsuario}' AND s.user_id=u.id_user)
                    ,true)AS sub, ac.estado_pedido, ac.estado as estado_a, ac.id_asignacion_cliente, CONCAT(em.nombres,' ',em.paterno,' ',em.materno) as distribuidor, i.cobrar
                FROM inv_egresos i
                LEFT JOIN inv_almacenes a ON i.almacen_id=a.id_almacen
                LEFT JOIN sys_empleados e ON i.empleado_id=e.id_empleado
                LEFT JOIN sys_users AS u ON e.id_empleado=u.persona_id
                LEFT JOIN inv_clientes c ON i.cliente_id = c.id_cliente
                LEFT JOIN inv_asignaciones_clientes ac ON i.id_egreso = ac.egreso_id AND ac.estado = 'A'
                LEFT JOIN sys_empleados em ON ac.distribuidor_id=em.id_empleado
                WHERE (i.estadoe>2 OR i.estadoe<=4)				
				AND i.tipo IN('Preventa', 'No venta')
				AND (i.preventa != 'eliminado' AND i.preventa != 'anulado' OR i.preventa <=> NULL))
				AND e.id_empleado = '$empleado_id'
				ORDER BY i.fecha_egreso DESC, i.hora_egreso DESC")->fetch();
}

// echo json_encode($proformas); die();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables

$permiso_cambiar = true;

$permiso_imprimir = false;

$permiso_editar = true;//in_array('preventas_editar', $permisos);
$permiso_ver = in_array('preventas_ver', $permisos);
$permiso_eliminar = true; //in_array('preventas_eliminar', $permisos);




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
		<strong>Preventas</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_cambiar || $permiso_crear) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para listar preventas por fecha, presionar el siguiente boton: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_cambiar) { ?>
			<button class="btn btn-default" data-cambiar="true"><i class="glyphicon glyphicon-calendar"></i><span class="hidden-xs"> Cambiar</span></button>
			<?php } ?>
			
			<?php if ($permiso_crear) { ?>
			<!--<a href="?/preventas/seleccionar_almacen" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span><span class="hidden-xs"> Nueva preventa</span></a>-->
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<?php if ($proformas) { ?>
	<table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
		<thead>
			<tr class="active">
				<th class="text-nowrap">Nro. Nota</th>
				<th class="text-nowrap">Fecha</th>
				<th class="text-nowrap">Cliente</th>
				<th class="text-nowrap">NIT/CI</th>
				<th class="text-nowrap">Monto total <?= escape($moneda); ?></th>
				<th class="text-nowrap">Registros</th>
				<th class="text-nowrap">Almacen</th>
				<th class="text-nowrap">Empleado</th>
				<th class="text-nowrap">Estado</th>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
				<th class="text-nowrap">Opciones</th>
				<?php } ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Nro. Nota</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Cliente</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">NIT/CI</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Monto total <?= escape($moneda); ?></th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Registros</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Almacen</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Empleado</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Estado</th>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($proformas as $nro => $proforma) { ?>
			<?php 
    		    $id_motivo = $proforma['motivo_id'];
                $nombre_motivo = $db->query("select * from gps_noventa_motivos where id_motivo = '$id_motivo'")->fetch_first();
            ?>
			<tr>
				<td class="text-nowrap text-right"><?= escape($proforma['nro_nota']); ?></td>
				<td class="text-nowrap"><?= escape(date_decode($proforma['fecha_egreso'], $_institution['formato'])); ?> <small class="text-success"><?= escape($proforma['hora_egreso']); ?></small></td>
				<td class="text-nowrap"><?= escape($proforma['nombre_cliente']); ?></td>
				<td class="text-nowrap"><?= escape($proforma['nit_ci']); ?></td>
				<td class="text-nowrap text-right"><?= escape(number_format($proforma['monto_total'],2,',','.')); ?></td>
				<td class="text-nowrap text-right"><?= escape($proforma['nro_registros']); ?></td>
				<td class="text-nowrap"><?= escape($proforma['almacen']); ?></td>
				<td class="width-md"><?= escape($proforma['nombres'] . ' ' . $proforma['paterno'] . ' ' . $proforma['materno']); ?></td>
				<td class="width-md">
				
				<?php 
				// echo $proforma['preventa']." - - - ".$proforma['estado_pedido'];
				
                if ($proforma['preventa'] == NULL && $proforma['estado_pedido'] == NULL):
                    echo '<span style="color: red;">No esta habilitado</span>';
                
                elseif ($proforma['preventa'] == 'habilitado' && $proforma['estado_pedido'] == 'salida'):
                    echo 'Ya fue asignado al distribuidor ('.$proforma['distribuidor'].')';
                
                elseif ($proforma['preventa'] == 'habilitado' && $proforma['estado_pedido'] == 'entregado'):
                    echo '<span style="color: green;">Ya fue entregado</span>';
                
                elseif ($proforma['preventa'] == 'habilitado' && $proforma['estado_pedido'] == NULL):
                    echo '<span style="color: blue;">Aun no fue asignado a un repartidor</span>';
                
                elseif ($proforma['preventa'] == NULL && $proforma['estado_pedido'] == 'reasignado') :
                    echo '<span style="color: blue;">Puede reasignar ('.$nombre_motivo['motivo'].')</span>';
                
                elseif ($proforma['preventa'] == 'habilitado' && $proforma['estado_pedido'] == 'reasignado') :
                    echo '<span style="color: blue;">Puede reasignar ('.$nombre_motivo['motivo'].')</span>';
                
                elseif ($proforma['preventa'] == 'anulado') :
                    echo '<span style="color: red;">Anulado</span>';
                endif;
                ?></td>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
				<td class="text-nowrap">
				    
					<?php if ($permiso_ver){ ?>
					<a href="?/preventas/ver/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Ver detalle de la preventa"><i class="glyphicon glyphicon-list-alt"></i></a>
					<?php } ?>
					
					<?php if ($permiso_eliminar && false){ ?>
					<a href="?/preventas/eliminar/<?= $proforma['id_egreso']; ?>" data-toggle="tooltip" data-title="Eliminar preventa" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span></a>
					<?php } ?>
					
					<?php if($permiso_editar && $proforma['preventa']==NULL){ ?>
                        <a href='?/operaciones/preventas_editar/<?= $proforma['id_egreso']; ?>'data-toggle='tooltip' data-title='Editar preventa' title='Editar preventa'><span class='glyphicon glyphicon-edit'></span></a>
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
		<p>No existen preventas registrados en la base de datos.</p>
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
	<?php if ($permiso_crear) { ?>
	$(window).bind('keydown', function (e) {
		if (e.altKey || e.metaKey) {
			switch (String.fromCharCode(e.which).toLowerCase()) {
				case 'n':
					e.preventDefault();
					window.location = '?/preventas/crear';
				break;
			}
		}
	});
	<?php } ?>

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

	<?php if ($proformas) { ?>
	var table = $('#table').DataFilter({
		filter: true,
		name: 'Preventas personales',
		fechas: '',
        creacion: "Fecha y hora de Impresion: <?= date("d/m/Y H:i:s") ?>",
        total: 6,
		reports: 'excel|pdf'
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
			
			window.location = '?/notas/mostrar' + inicial_fecha + final_fecha;
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
});
</script>
<?php require_once show_template('footer-advanced'); ?>