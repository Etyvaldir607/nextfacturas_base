<?php

// Obtiene el id_venta
$id_venta = (isset($params[0])) ? $params[0] : 0;
$id_venta_333 = (isset($params[1])) ? $params[1] : 0;

// Obtiene los formatos para la fecha
$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

$proformas=$db->query(" SELECT i.*,a.almacen,a.principal,e.nombres,e.paterno,e.materno,e.cargo
        				FROM inv_egresos i
        				LEFT JOIN inv_almacenes a ON i.almacen_id=a.id_almacen
        				LEFT JOIN sys_empleados e ON i.vendedor_id=e.id_empleado
        				WHERE i.tipo IN('Preventa', 'No venta','Venta')
        				        AND preventa is NULL OR (preventa='habilitado' AND estadoe!=3)
        				ORDER BY i.fecha_egreso DESC, i.hora_egreso DESC
        				")->fetch_first();

$Sentenciax="SELECT  DISTINCT(i.nro_movimiento), i.*,c.codigo,a.almacen,a.principal,e.nombres,e.paterno,e.materno,e.cargo,
                        ac.estado_pedido, ac.estado as estado_a, ac.id_asignacion_cliente, CONCAT(em.nombres,' ',em.paterno,' ',em.materno) as distribuidor, i.plan_de_pagos
                FROM inv_egresos i
                LEFT JOIN inv_almacenes a ON i.almacen_id=a.id_almacen
                LEFT JOIN sys_empleados e ON i.vendedor_id=e.id_empleado
                LEFT JOIN sys_users AS u ON e.id_empleado=u.persona_id
                LEFT JOIN inv_clientes c ON i.cliente_id = c.id_cliente
                LEFT JOIN inv_asignaciones_clientes ac ON i.id_egreso = ac.egreso_id AND ac.estado = 'A'
                LEFT JOIN sys_empleados em ON ac.distribuidor_id=em.id_empleado
                
                LEFT join inv_clientes_grupos cg on cg.id_cliente_grupo=i.codigo_vendedor
                LEFT JOIN sys_supervisor ss ON cg.id_cliente_grupo=ss.cliente_grupo_id
                        
                LEFT JOIN inv_users_almacenes ua ON ua.almacen_id=i.almacen_id
                
                WHERE i.tipo IN('Preventa', 'No venta', 'Venta')
                        AND (
				            preventa is NULL
				                OR 
				            (preventa='habilitado' AND estadoe!=3)
				            )
				        AND ( 
				            NOT ( (preventa='habilitado' OR preventa is NULL) AND estadoe=4 AND estado_pedido!='reasignado') 
				        )
				        AND (
        			        (i.vendedor_id='".$_user['persona_id']."' AND '".$_user['rol_id']."' = 15)
        			        OR 
        			        (ss.user_ids='".$_user['id_user']."' AND '".$_user['rol_id']."' = 14)
        			        OR 
        			        '".$_user['rol_id']."' = 1
        			        OR 
        			        (ua.user_id='".$_user['id_user']."' AND '".$_user['rol_id']."' = 17)
        			    )
				";            
    
$egresosx=$db->query($Sentenciax)->fetch();

foreach($egresosx as $key=>$Dato){
	if(!$Dato['estado_a'] || $Dato['preventa'] == NULL){
		if($Dato['preventa'] == 'habilitado'){
			echo '<input type="checkbox" id="id_hide_'.$Dato['id_egreso'].'" name="id_hide[]" value="'.$Dato['id_egreso'].'" style="display:none;" >';
			
		}
	}else{
		if($Dato['estadoe'] == '4' && $Dato['preventa'] != NULL){
			echo '<input type="checkbox" id="id_hide_'.$Dato['id_egreso'].'" name="id_hide[]" value="'.$Dato['id_egreso'].'" style="display:none;" >';
		}
	}
}

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_ver = in_array('preventas_ver', $permisos);
$permiso_cliente = in_array('preventas_asignar', $permisos);
$permiso_habilitar=in_array('preventas_habilitar', $permisos);
$permiso_asignaciones = in_array('asignaciones', $permisos);
$permiso_asignacion_varias = in_array('asignacion_distribuidor', $permisos);
$permiso_anuladas = in_array('preventas_anuladas', $permisos);
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
		<strong>Lista de ventas</strong>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_cambiar || $permiso_asignaciones || $permiso_anuladas) { ?>
		<form method="POST" action="?/asignacion/asignacion_distribuidor" class="form-horizontal">
			<div class="row">
				<div class="col-sm-7 hidden-xs">
					<div class="text-label">Para realizar una acción hacer clic en los siguientes botones: </div>
				</div>
				<div class="col-xs-12 col-sm-5 text-right">
					<?php if ($permiso_asignacion_varias) { ?>
						<button type="submit" class="btn btn-primary">
            				<span class="glyphicon glyphicon-floppy-disk"></span>
            				<span>Asignar Distribuidor</span>
            			</button>
					<?php } ?>
					<?php if ($permiso_anuladas) { ?>
						<a href="?/asignacion/preventas_anuladas" class="btn btn-danger"><i class="glyphicon glyphicon-eye-open"></i><span class="hidden-xs"> Ver anulados</span></a>
					<?php } ?>
					<?php if ($permiso_asignaciones) { ?>
						<a href="?/asignacion/asignaciones" class="btn btn-info"><i class="glyphicon glyphicon-eye-open"></i><span class="hidden-xs"> Ver asignaciones</span></a>
					<?php } ?>
					<?php if ($permiso_cambiar && false) { ?>
						<button class="btn btn-default" data-cambiar="true"><i class="glyphicon glyphicon-calendar"></i><span class="hidden-xs"> Cambiar</span></button>
					<?php } ?>
				</div>
			</div>
			<hr>
			<?php } ?>
			<?php if (isset($_SESSION[temporary])) { ?>
			<div class="alert alert-<?= (isset($_SESSION[temporary]['alert'])) ? $_SESSION[temporary]['alert'] : $_SESSION[temporary]['type']; ?>">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				<strong><?= $_SESSION[temporary]['title']; ?></strong>
				<p><?= (isset($_SESSION[temporary]['message'])) ? $_SESSION[temporary]['message'] : $_SESSION[temporary]['content']; ?></p>
			</div>
			<?php unset($_SESSION[temporary]); ?>

			<?php } ?>
			<?php if ($proformas) { ?>
			<table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
				<thead>
					<tr class="active">
						<th class="text-nowrap">Nro. Nota</th>
						<th class="text-nowrap">Fecha</th>
						<th class="text-nowrap">Cliente</th>
						<th class="text-nowrap">Total <?= escape($moneda); ?></th>
						<th class="text-nowrap">Tipo</th>
						<th class="text-nowrap">Forma pago</th>
						<th class="">Observacion</th>
						<th class="text-nowrap">Almacen</th>
						<th class="text-nowrap">Vendedor</th>
						<th class="text-nowrap">Estado</th>
						<th class="text-nowrap">Distribuidor</th>
						<?php if ($permiso_cliente || $permiso_habilitar) { ?>
							<th class="text-nowrap">Acciones</th>
							<th class="text-nowrap"></th>
						<?php } ?>
					</tr>
				</thead>
				<tfoot>
					<tr class="active">
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Nro. Nota</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Cliente</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Total <?= escape($moneda); ?></th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Tipo</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Forma pago</th>
						<th class="text-middle" data-datafilter-filter="true">Observacion</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Almacen</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Vendedor</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Estado</th>
						<th class="text-nowrap text-middle" data-datafilter-filter="true">Distribuidor</th>
						<?php if ($permiso_cliente || $permiso_habilitar) { ?>
							<th class="text-nowrap text-middle" data-datafilter-filter="false">Acciones</th>
							<th class="text-nowrap text-middle" data-datafilter-filter="false"></th>
						<?php } ?>
						<!-- <th class="text-nowrap" data-datafilter-filter="false"></th> -->
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
		</form>
	<?php } ?>
    <div class="well">
        <p class="lead margin-none">
            <b>Total:</b>
            <u id="total">0.00</u>
            <span><?= escape($moneda); ?></span>
        </p>
    </div>
</div>

<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.maskedinput.min.js"></script>
<script src="<?= js; ?>/jquery.base64.js"></script>
<script src="<?= js; ?>/pdfmake.min.js"></script>
<script src="<?= js; ?>/vfs_fonts.js"></script>
<!--<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>-->
<script src="<?= js; ?>/dataFiltersCustom.min.js"></script>
<script src="<?= js; ?>/moment.min.js"></script>
<script src="<?= js; ?>/moment.es.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>
<script>
$(function () {

    <?php if($id_venta){ ?>
        imprimir_nota_final(<?= $id_venta ?>,<?= $id_venta_333 ?>);
    <? } ?>

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
    var table = $('#table').on('draw.dt', function () {   // search.dt order.dt page.dt length.dt
        var suma = 0;
        $('[data-total]:visible').each(function (i) {
            var total = parseFloat($(this).attr('data-total'));
            console.log(total);
            suma = suma + total;
        });
        $('#total').text(suma.toFixed(2));
        
    }).DataFilter({
        
        filter: false,
		name: 'Asignar ventas',
        
        empresa: '<?= $_institution['nombre']; ?>',
        direccion: '<?= $_institution['direccion'] ?>',
        telefono: '<?= $_institution['telefono'] ?>',
		fechas: '',
		creacion: "Fecha y hora de Impresion: <?= date("d/m/Y H:i:s") ?>",
        total: '7',	
        reports: 'excel|pdf',
		size: 8,
        values: {
            serverSide: true,
            lengthMenu: [[1000, 10, 25, 50], ["Todos", 10, 25, 50]],
            order: [[0, 'asc']],
            ajax: {
                url: '?/asignacion/preventas_listar2',
                type: 'post',
                beforeSend:function(){
                    // $loader_mostrar.show();
                },
                error: function () {}
            },
            drawCallback: function(settings) {
                //$loader_mostrar.hide();
            },
            createdRow:function(nRow, aData, iDisplayIndex){

				// console.log(nRow);

                $(nRow).attr('data-producto',aData[0]);
                $('td', nRow).eq(0).addClass('text-middle');
				$('td', nRow).eq(1).addClass('text-nowrap text-middle');
				$('td', nRow).eq(2).addClass('text-middle');
				$('td', nRow).eq(3).addClass('text-nowrap text-middle text-right').attr('data-total',aData[16]);
				$('td', nRow).eq(4).addClass('text-nowrap text-middle');
				$('td', nRow).eq(5).addClass('text-middle');
				$('td', nRow).eq(6).addClass('text-middle');
				$('td', nRow).eq(7).addClass('text-middle');
				$('td', nRow).eq(8).addClass('text-middle');
				$('td', nRow).eq(9).addClass('text-middle');
				$('td', nRow).eq(10).addClass('');
				$('td', nRow).eq(11).addClass('');
				$('td', nRow).eq(12).addClass('');
				$('td', nRow).eq(13).addClass('');
				$('td', nRow).eq(14).addClass('');
				$('td', nRow).eq(15).addClass('');
				$('td', nRow).eq(16).addClass('text-nowrap text-middle');
            }
        }
    });
	<?php } ?>
});

// function entregar_asignacion(id_asignacion) {
// 	bootbox.confirm('Está seguro de entregar la asignación? no podrá rehacer esta acción.', function (result) {
//                     if(result){
//                         window.location = '?/asignacion/preventas_entregar/' + id_asignacion;
//                     }
//                 });
// }

function eliminarar_asignacion(id_asignacion){
	bootbox.confirm('Está seguro que desea eliminar la asignación? tendra que volver a asignar un distribuidor para esta preventa', function (result) {
                    if(result){
                        window.location = '?/asignacion/preventas_eliminar/' + id_asignacion;
                    }
                });
}
function eliminar_nota_venta(id_venta){
	bootbox.confirm('Está seguro que desea anular la preventa? tenga en cuenta que esta acción no se podra rehacer.', function (result) {
                    if(result){
                        window.location = '?/asignacion/anular/' + id_venta;
                    }
                });
}
function entregar_asignacion(id_asignacion, id_egreso) {
    var dialog = bootbox.dialog({
        title: 'Está seguro de entregar la asignación? no podrá rehacer esta acción.',
        message: '<p><i class="fa fa-spin fa-spinner"></i> Cargando detalle...</p>',
        size: 'large',
        buttons: {
            noclose: {
                label: "Editar Venta",
                className: 'btn-warning',
                callback: function(){
                    // console.log('Custom button clicked');
                    // return false;
                    window.location = '?/asignacion/preventa_distribucion_editar/' + id_egreso +'/1';
                }
            },
            ok: {
                label: "Aceptar!",
                className: 'btn-primary',
                callback: function(){
                    // console.log('Custom OK clicked');
                    window.location = '?/asignacion/preventas_entregar/' + id_asignacion + '/' + id_egreso;
                }
            },
            cancel: {
                label: "Cancelar",
                className: 'btn-default',
                callback: function(){
                    window.location = '?/asignacion/preventas_listar';
                }
            }
        }
    });
    dialog.init(function(){
        setTimeout(function(){
            dialog.find('.bootbox-body').load("?/asignacion/detallando/" + id_egreso);
        }, 500);
    });
}
function imprimir_nota_final(nota,recibo) {
	bootbox.confirm('¿Desea imprimir la nota de venta?', function(result) {
		if (result) {
			$.open('?/notas/imprimir_nota/' + nota, true);
			if(recibo != 0){
				imprimir_recibo(recibo);
			}
		}
		else{
			if(recibo != 0){
				imprimir_recibo(recibo);
			}
		}
	});
}
function imprimir_recibo(nota) {
	bootbox.confirm('¿Desea Imprimir el recibo?', function(result) {
		if (result) {
			window.open('?/cobrar/recibo_dinero/' + nota, true);
			//window.location.reload();
		}
		else{
			//window.location.reload();
		}
	});
}
function checkk(nro){
	var n = $( "#id_detalle_"+nro+":checked").length;
	if(n==0){
		$( "#id_hide_"+nro ).prop( "checked", false );
	}else{
		$( "#id_hide_"+nro ).prop( "checked", true );
	}
}
</script>
<?php require_once show_template('footer-advanced');