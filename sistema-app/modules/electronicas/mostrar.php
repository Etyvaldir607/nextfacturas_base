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

// Obtiene el id_venta
$id_venta = (isset($params[2])) ? $params[2] : 0;

// Obtiene las ventas
$query = "SELECT i.*, f.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, ac.estado_pedido, 
                CONCAT(em.nombres,' ',em.paterno,' ',em.materno) as distribuidor, cg.nombre_grupo, cl.cliente, ppp.suma_pagos
            			 from inv_egresos i 
            			 left join inv_egresos_facturas f on i.id_egreso = f.egreso_id
                         left join inv_almacenes a on i.almacen_id = a.id_almacen
                         left join sys_empleados e on i.vendedor_id = e.id_empleado
            			 LEFT JOIN inv_asignaciones_clientes ac ON i.id_egreso = ac.egreso_id AND ac.estado = 'A'
                         LEFT JOIN sys_empleados em ON ac.distribuidor_id=em.id_empleado
                         
                         LEFT JOIN sys_users u ON u.persona_id=i.vendedor_id
                         
                         LEFT join inv_clientes_grupos cg on cg.id_cliente_grupo=i.codigo_vendedor
					     LEFT JOIN sys_supervisor ss ON cg.id_cliente_grupo=ss.cliente_grupo_id
                         
                         LEFT JOIN inv_clientes cl ON cl.id_cliente=i.cliente_id
                         
                         LEFT JOIN(
                            SELECT sum(pd.monto)suma_pagos, pp.movimiento_id
                            FROM inv_pagos pp 
                            LEFT JOIN inv_pagos_detalles pd ON pp.id_pago=pd.pago_id
                            WHERE pp.tipo='Egreso' AND estado=1
                            GROUP BY pp.movimiento_id
                        )ppp on ppp.movimiento_id=i.id_egreso
                        
                         WHERE 
            			     i.tipo IN('Preventa', 'Venta', 'No venta', 'Devolucion')
    				         AND (preventa is NULL OR preventa='habilitado' OR preventa='anulado')  
            			     
            			     AND (
            			        (i.vendedor_id='".$_user['persona_id']."' AND '".$_user['rol_id']."' = 15)
            			        OR 
            			        (ss.user_ids='".$_user['id_user']."' AND '".$_user['rol_id']."' = 14)
            			        OR 
            			        '".$_user['rol_id']."' = 1
            			        OR 
            			        '".$_user['rol_id']."' = 22
            			    )
            			     
            			    AND i.fecha_habilitacion >= '".$fecha_inicial." 00:00:00'
                			AND i.fecha_habilitacion <= '".$fecha_final." 23:59:59'
                		    AND i.nro_nota != '0'
                		
                		GROUP BY nro_nota
                		
                		ORDER BY nro_nota DESC ";

$ventas = $db->query($query)->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_ver = in_array('ver', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_editar = in_array('editar', $permisos);
$permiso_anular = in_array('anular', $permisos);
$permiso_imprimir_nota = in_array('imprimir_nota', $permisos);
$permiso_cambiar = true;

$permiso_facturar = true;//in_array('preventas_facturar', $permisos);
$permiso_devolucion = in_array('notas_devolucion', $permisos);

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
		<b>Lista de Ventas</b>
	</h3>
</div>

<?php 
//var_dump($_user); 
//echo "<br>".$query
?>

<div class="panel-body">
	<?php if ($permiso_cambiar || $permiso_crear) { ?>
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<!-- <div class="text-label">Para realizar una nota de venta hacer clic en el siguiente botÃ³n: </div> -->
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<?php if ($permiso_cambiar) { ?>
    			<button class="btn btn-default" data-cambiar="true">
    				<span class="glyphicon glyphicon-calendar"></span>
    				<span class="hidden-xs">Cambiar</span>
    			</button>
			<?php } ?>

            <?php if ($permiso_crear) { ?>
                 <a href="?/electronicas/seleccionar_almacen" class="btn btn-danger"><span class="glyphicon glyphicon-plus"></span><span class="hidden-xs"> Vender</span></a>                 
            <?php } ?>

            <?php if ($permiso_crear) { ?>
                 <a href="?/electronicas/listar_facturas" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span><span class="hidden-xs"> Listar Facturas</span></a>                 
            <?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	<?php if ($ventas) { ?>
	<table id="table" class="table table-bordered table-condensed table-striped table-hover table-xs">
		<thead>
			<tr class="active">
				<th class="text-nowrap">Nro nota</th>
				<th class="text-nowrap">Fecha</th>

                <th class="text-nowrap">Nro factura</th>
				<th class="text-nowrap">Fecha Factura</th>
				<!--<th class="text-nowrap">Tipo</th>-->

				<th class="text-nowrap">Cliente</th>
				<th class="text-nowrap">Monto total <?= escape($moneda); ?></th>
				<th class="text-nowrap">Almacen</th>
                <th class="text-nowrap">Grupo</th>
				<th class="text-nowrap">Vendedor</th>
				<th class="text-nowrap">Estado</th>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
			    <th class="text-nowrap">Opciones</th>
				<?php } ?>
				<?php if ($permiso_imprimir_nota) { ?>
				<th class="text-nowrap">Imprimir</th>
				<?php } ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="active">
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Nro nota</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha</th>

                <th class="text-nowrap text-middle" data-datafilter-filter="true">Nro factura</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Fecha Factura</th>
				<!--<th class="text-nowrap text-middle" data-datafilter-filter="true">Tipo</th>-->

				<th class="text-nowrap text-middle" data-datafilter-filter="true">Cliente</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Monto total <?= escape($moneda); ?></th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Almacen</th>
                <th class="text-nowrap text-middle" data-datafilter-filter="true">Grupo</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Vendedor</th>
				<th class="text-nowrap text-middle" data-datafilter-filter="true">Estado</th>
				<?php if ($permiso_ver || $permiso_eliminar) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Opciones</th>
				<?php } ?>
				<?php if ($permiso_imprimir_nota) { ?>
				<th class="text-nowrap text-middle" data-datafilter-filter="false">Imprimir</th>
				<?php } ?>
			</tr>
		</tfoot>
		<tbody>
			<?php 
			foreach ($ventas as $nro => $venta) { 
			    if(escape($venta['codigo_control']) == ''){
    			    $tipo = escape($venta['tipo']);
    			}else{
    			    $tipo = 'Venta electronica';
    			} 
    			
    			$ventas_anuladas = $db->query("  select COUNT(i.id_egreso) as cantidad_notas
                                    			 from inv_egresos i 
                                    			 WHERE nro_nota='".$venta['nro_nota']."' AND nro_nota!=0
                                    		  ")->fetch_first();
                
                $Auxv=explode(" ",$venta['fecha_habilitacion']);
                
                $valor_limite = isset($venta['fecha_factura'])? $venta['fecha_factura']:"0000-00-00 00:00:00";
                $Auxv222=explode(" ",$valor_limite);
                
                if($ventas_anuladas['cantidad_notas']<=1 || ($venta['preventa']!='anulado') ){
            	
            	    $ventas_pagos = $db->query("select *
                                        		from inv_pagos i 
                                        		left join inv_pagos_detalles d on i.id_pago = d.pago_id
    			                                WHERE movimiento_id='".$venta['id_egreso']."' AND d.estado=1 AND i.tipo='Egreso' AND d.tipo_pago!='devolucion' 
                                        	  ")->fetch();
                    
                    $ventas_pagos2 = $db->query("select *
                                        		from inv_ingresos i 
                                        		left join inv_pagos_detalles pd on i.id_ingreso = pd.ingreso_id
    			                                left join inv_pagos p on p.id_pago = pd.pago_id AND p.tipo='Egreso'
    			                                WHERE p.movimiento_id='".$venta['id_egreso']."' and (pd.tipo_pago='Devolucion')
    			                              ")->fetch();
                    
            	
            		?>
        			<tr>
        				<td class="text-nowrap text-right"><?= escape($venta['nro_nota']); ?></td>
        				<td class="text-nowrap"><?= escape(date_decode($Auxv[0], $_institution['formato'])); ?><br><small class="text-success"><?= escape($Auxv[1]); ?></small></td>

                        <td class="text-nowrap text-right"><?= escape($venta['nro_factura']); ?></td>
        				<?php if($Auxv222[0]!="0000-00-00"){ ?>
            				<td class="text-nowrap"><?= escape(date_decode($Auxv222[0], $_institution['formato'])); ?><br><small class="text-success"><?= escape($Auxv222[1]); ?></small></td>
        				<?php }else{ ?>
            				<td class="text-nowrap"></td>
        				<?php } ?>

        				<!--<td class="text-nowrap"><?= escape($tipo); ?></td>-->
        				<td class=""><?= escape($venta['cliente']); ?></td>
        				<td class="text-nowrap text-right"><?= escape(number_format($venta['monto_total'],2 , ',', '.')); ?></td>
        		        <td class="text-nowrap"><?= escape($venta['almacen']); ?></td>
                		
        				<td class=""><?= escape($venta['nombre_grupo']); ?></td>
        				<td class=""><?= escape($venta['nombres'] . ' ' . $venta['paterno'] . ' ' . $venta['materno']); ?></td>
        				
    					<?php 
        				if($Auxv222[0]!="0000-00-00"){
            			    echo '<td class="success"><span style="">Facturado</span></td>';
                        }else{
                        	if($venta['factura'] == "si"){
                            	echo '<td class="danger"><span style="">Por Facturar</span></td>';
                        	}else{
    							echo '<td class="primary"><span style="">No Facturado</span></td>';
                        	}
                        }
                        ?>
    				
        				<?php if ($permiso_ver || $permiso_eliminar) { ?>
        				<td class="text-nowrap">
        					<?php if ($permiso_ver) { ?>
        						<a href="?/electronicas/ver/<?= $venta['id_egreso']; ?>" data-toggle="tooltip" data-title="Ver detalle de nota de venta"><span class="glyphicon glyphicon-list-alt"></span></a>
        					<?php } ?>
        					
        					<?php if($permiso_editar){ ?>
            					<?php if($venta['preventa'] == 'habilitado' && $venta['tipo'] == 'Preventa' && $venta['estado_pedido']==""){ ?>
            					    <!-- <a href="?/electronicas/editar/<?= $venta['id_egreso']; ?>" data-toggle="tooltip" data-title="Editar nota de venta"><span class="glyphicon glyphicon-edit"></span></a> -->
            					<?php } ?>
        					<?php } ?>
        					
        					<?php 
        					if ($permiso_anular && $venta['preventa'] != 'anulado' && !$ventas_pagos && !$ventas_pagos2){
				            ?>
        					    <!-- <a onclick="eliminar_nota_venta('<?= $venta['id_egreso'] ?>');" data-toggle="tooltip" data-title="Eliminar nota de venta" data-eliminar="true"><span class="glyphicon glyphicon-trash"></span></a> -->
        					<?php } ?>
        					
        					<?php if($permiso_devolucion && $venta['preventa'] != 'anulado'){ ?>
                                <?php if ($venta['estadoe'] == '3' || ($venta['distribuir'] == 'N' && $venta['estadoe']==0) ){ ?>
                                    <!-- <a href='?/electronicas/notas_devolucion/<?= $venta['id_egreso']; ?>' data-toggle='tooltip' data-title='Devolucion' title='Devolucion'><span class='glyphicon glyphicon-transfer'></span></a> -->
                                <? } ?>
                            <? } ?>
                            
							<?php 
        					if($Auxv222[0]=="0000-00-00"){
        					?>	
	            				&nbsp;
	           					<a href="?/electronicas/facturar_siat/<?= $venta['id_egreso']; ?>" data-toggle="tooltip" data-title="Facturar" target="_self">
	           						<span class="glyphicon glyphicon-qrcode" target='_blank'></span>
	           					</a>
							<?php 
        					}
        					?>

           					<?php 
        					if($Auxv222[0]!="0000-00-00"){
        					?>	
            					&nbsp;
           						<a href="?/siat/facturas/<?= $venta['id_egreso']; ?>/view" data-toggle="tooltip" data-title="Imprimir Factura" target="_blank"><span class="glyphicon glyphicon-print" target='_blank'></span></a>
                            <?php 
        					}
        					?>	
            				
        				</td>
        				<?php } ?>
				        <?php if ($permiso_imprimir_nota) { ?>
						<td class="text-nowrap">
				            
				            <?php
				            if ($venta['preventa'] != 'anulado'){
				            ?>
    				            <a href="?/electronicas/imprimir_nota/<?= $venta['id_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir" target="_blank"><span class="glyphicon glyphicon-print" style="color:green"></span></a>
                                
                                <?php if ($venta['nro_factura']!=0) { ?>
                                    <a href="?/operaciones/refacturado_imprimir/<?= $venta['id_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir Factura" target="_blank"><span class="glyphicon glyphicon-qrcode" style="color:blue"></span></a>
                                <?php } 
                                
                            	$ventas_pagos = $db->query("select *
                                                    		from inv_pagos i 
                                                    		left join inv_pagos_detalles d on i.id_pago = d.pago_id
                			                                WHERE movimiento_id='".$venta['id_egreso']."' AND d.estado=1 AND i.tipo='Egreso' AND d.tipo_pago!='devolucion' 
                                                    	  ")->fetch();
                                                    		  
                                foreach ($ventas_pagos as $n3o => $ventas_pago) { 
                                    ?>
                                    <?php if ($venta['estadoe']==3) { ?>
                                        <a href="?/cobrar/recibo_dinero/<?= $ventas_pago['id_pago_detalle']; ?>" data-toggle="tooltip" data-title="Imprimir Recibo" target="_blank"><span class="glyphicon glyphicon-tag" style="color:blue"></span></a>
                                    <?php 
                                    } 
                                }
                                
                                // $ventas_pagos2 = $db->query("select *
                                //                     		from inv_egresos e 
                                //                     		left join inv_egresos_detalles d on e.id_egreso = d.egreso_id
                			             //                   WHERE nro_nota='".$venta['nro_nota']."' and (tipo='No venta' OR tipo='Devolucion')
                			             //                   GROUP BY fecha_devolucion
                                //                     	  ")->fetch();
                                                    		  
                                // foreach ($ventas_pagos2 as $n3o => $ventas_pagox) { 
                                ?>
                                    <!--a href="?/electronicas/imprimir_nota_credito/<?php // $ventas_pagox['id_egreso']; ?>" data-toggle="tooltip" data-title="Imprimir nota de credito" target="_blank"-->
                                    <!--    <span class="glyphicon glyphicon-import" style="color:red"></span>-->
                                    <!--</a>-->
                                <?php 
                                //}



                                $ventas_pagos2 = $db->query("select *
                                                    		from inv_ingresos i 
                                                    		left join inv_pagos_detalles pd on i.id_ingreso = pd.ingreso_id
                			                                left join inv_pagos p on p.id_pago = pd.pago_id AND p.tipo='Egreso'
                			                                WHERE p.movimiento_id='".$venta['id_egreso']."' and (pd.tipo_pago='Devolucion')
                			                              ")->fetch();
                                                    		  
                                foreach ($ventas_pagos2 as $n3o => $ventas_pagox) { 
                                ?>
                                    <a href="?/electronicas/imprimir_nota_credito/<?php echo $ventas_pagox['id_ingreso']; ?>" data-toggle="tooltip" data-title="Imprimir nota de credito" target="_blank">
                                        <span class="glyphicon glyphicon-import" style="color:red"></span>
                                    </a>
                                <?php 
                                }
				            }
                            ?>
        				</td>
        				<?php } ?>
        			</tr>
        			<?php 
			    }
			} 
			?>
		</tbody>
	</table>
	<?php } else { ?>
	<div class="alert alert-danger">
		<strong>Advertencia!</strong>
		<p>No existen notas de venta registradas en la base de datos.</p>
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
<script src="<?= js; ?>/jquery.dataFilters.min.js"></script>
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
					window.location = '?/electronicas/seleccionar_almacen';
				break;
			}
		}
	});
	<?php } ?>

	<?php if ($permiso_eliminar) { ?>
	$('[data-eliminar]').on('click', function (e) {
		e.preventDefault();
		var url = $(this).attr('href');
		bootbox.confirm('EstÃ¡ seguro que desea eliminar la nota de venta y todo su detalle?', function (result) {
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
			
			window.location = '?/electronicas/mostrar' + inicial_fecha + final_fecha;
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
	
	<?php if ($ventas) { ?>
	var table = $('#table').DataFilter({
		filter: true,
		name: 'ventas_notas_personales',
		reports: 'excel|word|pdf|html',
		values: {
            order: [[0, 'desc']]
        }
	});
	<?php } ?>

	<?php if ($id_venta!=0) { ?>
   		window.open('?/siat/facturas/<?= $id_venta; ?>/view', true);
   		//window.location.reload();
	<?php } ?>
});

function eliminar_nota_venta(id_venta){
	bootbox.confirm('Est¨¢ seguro que desea anular la venta? tenga en cuenta que esta acci¨®n no se podra rehacer.', function (result) {
        if(result){
            window.location = '?/electronicas/anular/' + id_venta;
        }
    });
}

</script>
<?php require_once show_template('footer-advanced'); ?>