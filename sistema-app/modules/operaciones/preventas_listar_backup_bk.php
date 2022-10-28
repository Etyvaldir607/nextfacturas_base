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

// Obtiene las ventas
$proformas=$db->query("SELECT i.*,a.almacen,a.principal,e.nombres,e.paterno,e.materno,e.cargo
				FROM inv_egresos i
				LEFT JOIN inv_almacenes a ON i.almacen_id=a.id_almacen
				LEFT JOIN sys_empleados e ON i.empleado_id=e.id_empleado
				WHERE i.fecha_egreso>='{$fecha_inicial}' AND i.fecha_egreso<='{$fecha_final}' AND i.estadoe>'1'
				ORDER BY i.fecha_egreso DESC, i.hora_egreso DESC")->fetch_first();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_ver = in_array('preventas_ver', $permisos);
$permiso_eliminar = in_array('preventas_eliminar', $permisos);
$permiso_imprimir = false;
$permiso_facturar = in_array('preventas_facturar', $permisos);
$permiso_editar = in_array('preventas_editar', $permisos);
$permiso_devolucion = in_array('preventas_devolucion', $permisos);
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
		<strong>Lista de todas las Preventas</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_cambiar || $permiso_imprimir) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los siguientes botones: </div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_cambiar) { ?>
			<button class="btn btn-default" data-cambiar="true"><i class="glyphicon glyphicon-calendar"></i><span class="hidden-xs"> Cambiar</span></button>
			<?php } ?>
            <?php if ($permiso_imprimir) { ?>
                <a href="?/operaciones/notas_imprimir" class="btn btn-primary" target="_blank" data-imprimir="true"><span class="glyphicon glyphicon-file"></span><span class="hidden-xs"> Imprimir</span></a>
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
	<?php if ($proformas) { ?>
	<table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
		<thead>
			<tr class="active">
				<th class="text-nowrap">#</th>
				<th class="text-nowrap">Fecha</th>
				<th class="text-nowrap">Movimiento</th>
				<th class="text-nowrap">C&oacute;digo</th>
				<th class="text-nowrap">Cliente</th>
				<th class="text-nowrap">NIT/CI</th>
				<th class="text-nowrap">Nro.</th>
				<th class="text-nowrap">Monto total <?= escape($moneda); ?></th>
                <th class="text-nowrap">Registros</th>
				<th class="text-nowrap">Almacen</th>
                <th class="text-nowrap">Empleado</th>
                <th class="text-nowrap">Estado</th>
				<?php if ($permiso_facturar || $permiso_ver || $permiso_eliminar) { ?>
				<th class="text-nowrap">Opciones</th>
				<?php } ?>
                <!--<th class="text-nowrap">-->
                <!--    <input type="checkbox" class="text-checkbox" data-toggle="tooltip" data-title="Seleccionar producto" data-grupo-seleccionar="true">-->
                <!--</th>-->
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="false">#</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Movimiento</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">C&oacute;digo</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Cliente</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">NIT/CI</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Nro</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Monto total <?= escape($moneda); ?></th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Registros</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Almacen</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Empleado</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Estado</th>
				<?php if ($permiso_facturar || $permiso_ver || $permiso_eliminar) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
                <!--<th class="text-nowrap" data-datafilter-filter="false"></th>-->
			</tr>
		</tfoot>
		<tbody>
			
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen preventas registradas en la base de datos.</p>
	</div>
	<?php } ?>
    <!--div class="well">
        <p class="lead margin-none">
            <b>Total:</b>
            <u id="total">0.00</u>
            <span><?= escape($moneda); ?></span>
        </p>
    </div-->
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
$(function () {

	<?php if (isset($_SESSION['imprimir'])) { ?>
		$.open('?/preventas/imprimir_nota/<?= $_SESSION['imprimir'] ?>', true);
		<?php unset($_SESSION['imprimir']); ?>
	<?php } ?>

	<?php if ($permiso_eliminar) { ?>
	$(document).on('click', '[data-eliminar]', function(e) {
	//$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('Esta seguro que desea eliminar la preventa y todo su detalle?', function (result) {
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
			window.location = '?/operaciones/preventas_listar' + inicial_fecha + final_fecha;
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
    var $grupo_seleccionar = $('[data-grupo-seleccionar]'), $seleccionar = $('[data-seleccionar]'), $imprimir = $('[data-imprimir]');
    $grupo_seleccionar.on('change', function () {
        $seleccionar.prop('checked', $(this).prop('checked')).trigger('change');
    });

    $seleccionar.on('change', function () {
        var $this = $(this), todos = $seleccionar.size(), productos = [], check = 0;
        $seleccionar.filter(':checked').each(function () {
            productos.push($(this).attr('data-seleccionar'));
            check = check + 1;
        });
        if ($this.prop('checked')) {
            $this.closest('tr').addClass('info');
        } else {
            $this.closest('tr').removeClass('info');
        }
        switch (check) {
            case 0:
                $grupo_seleccionar.prop({
                    checked: false,
                    indeterminate: false
                });
                break;
            case todos:
                $grupo_seleccionar.prop({
                    checked: true,
                    indeterminate: false
                });
                break;
            default:
                $grupo_seleccionar.prop({
                    checked: false,
                    indeterminate: true
                });
                break;
        }
        productos = productos.join('-');
        productos = (productos != '') ? productos : 'true';
        $imprimir.attr('data-imprimir', productos);
    });
    $imprimir.on('click', function (e) {
        if ($imprimir.attr('data-imprimir') == 'true') {
            e.preventDefault();
            bootbox.alert('Debe seleccionar al menos una orden de compra.');
        } else {
            $imprimir.attr('href', $imprimir.attr('href') + '/' + $imprimir.attr('data-imprimir'));
            window.location.reload();
        }
    });

    <?php if ($proformas) { ?>
    var table = $('#table').on('search.dt order.dt page.dt length.dt', function () {
        var suma = 0;
        $('[data-total]:visible').each(function (i) {
            var total = parseFloat($(this).attr('data-total'));
            console.log(total);
            suma = suma + total;
        });
        $('#total').text(suma.toFixed(2));
    }).DataFilter({
        filter: false,
		imag: '<?= imgs . '/logo-color.png'; ?>',
        // imag2: '<?php  // $imag; ?>',
        empresa: '<?= $_institution['nombre']; ?>',
        direccion: '<?= $_institution['direccion'] ?>',
        telefono: '<?= $_institution['telefono'] ?>',
        
        name: ' Lista de preventas',
		fechas: 'Desde <?= $fecha_inicial ?> hasta <?= $fecha_final_excel ?> ',
        creacion: "Fecha y hora de Impresion: <?= date("d/m/Y H:i:s") ?>",
        total: '7',		
		
        reports: 'excel|pdf',
		size: 8,
        values: {
            serverSide: true,
            order: [[0, 'asc']],
            ajax: {
                url: '?/operaciones/preventas_listar2/<?=$fecha_inicial?>/<?=$fecha_final?>',
                type: 'post',
                beforeSend:function(){
                    //$loader_mostrar.show();
                },
                error: function () {}
            },
            drawCallback: function(settings) {
                //$loader_mostrar.hide();
            },
            createdRow:function(nRow, aData, iDisplayIndex){
                $(nRow).attr('data-producto',aData[0]);
                $('td', nRow).eq(0).addClass('text-nowrap');
				$('td', nRow).eq(1).addClass('text-nowrap');
				$('td', nRow).eq(2).addClass('text-nowrap');
				$('td', nRow).eq(3).addClass('text-nowrap');
				$('td', nRow).eq(4).addClass('text-nowrap');
				$('td', nRow).eq(5).addClass('text-nowrap text-right');
				$('td', nRow).eq(6).addClass('text-nowrap text-right');
				$('td', nRow).eq(7).addClass('text-nowrap text-right').attr('data-total',aData[15]);
				$('td', nRow).eq(8).addClass('text-nowrap text-right');
				$('td', nRow).eq(9).addClass('text-nowrap');
				$('td', nRow).eq(10).addClass('width-md');
				$('td', nRow).eq(11).addClass('width-md');
				$('td', nRow).eq(12).addClass('text-nowrap');
				$('td', nRow).eq(13).addClass('text-nowrap');
            }
        }
    });
	<?php } ?>
});
</script>
<?php require_once show_template('footer-advanced');