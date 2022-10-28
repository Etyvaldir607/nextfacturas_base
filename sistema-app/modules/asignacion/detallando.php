<?php

$formato_textual = get_date_textual($_institution['formato']);
$formato_numeral = get_date_numeral($_institution['formato']);

// Obtiene el id_venta
$id_venta = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la venta
$ventaQwr ="select i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, v.nombres nombresv, v.paterno paternov, v.materno maternov, i.plan_de_pagos, IFnull(p.id_pago,0)as id_pago, c.cliente
			from inv_egresos i
			left join inv_almacenes a ON i.almacen_id = a.id_almacen
			left join sys_empleados e ON i.empleado_id = e.id_empleado
			left join sys_empleados v ON i.vendedor_id = v.id_empleado
			left join inv_pagos p     ON p.movimiento_id = i.id_egreso AND p.tipo='Egreso'
			LEFT join inv_clientes c ON i.cliente_id = c.id_cliente
            where id_egreso='$id_venta'";

$venta = $db->query($ventaQwr)->fetch_first();

// Verifica si existe el egreso
if (!$venta) {
	// Error 404
	echo "El egreso no existe!!!";
}
else{
    // Obtiene los detalles
    $detalles = $db->query("SELECT d.*, SUM(d.cantidad) as cantidad, p.codigo, p.nombre, p.nombre_factura, u.unidad, d.cantidad AS tamanio
    						FROM inv_egresos_detalles d
    						LEFT JOIN inv_productos p ON d.producto_id = p.id_producto
    						LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id
    						LEFT JOIN inv_asignaciones a ON a.producto_id = d.producto_id AND a.unidad_id = d.unidad_id
    						LEFT JOIN inv_unidades u ON u.id_unidad = d.unidad_id
    						WHERE d.egreso_id = $id_venta
    						group by precio, producto_id, lote, vencimiento
                            ORDER BY codigo asc
                            ")->fetch();
    
    $detallesCuotas = $db->query(" select COUNT(pd.pago_id) AS NRO_LINES
                    			   from inv_pagos_detalles pd
                    			   where pd.pago_id ='".$venta['id_pago']."'
                    			   order by nro_cuota, fecha asc, fecha_pago asc")
    			         ->fetch_first();
    
    $NRO_LINES=$detallesCuotas['NRO_LINES'];
    
    // Obtiene los detalles
    $detallesCuota = $db->select('*')
    			   ->from('inv_pagos_detalles pd')
    			   ->where('pd.pago_id', $venta['id_pago'])
    			   ->order_by('nro_cuota, fecha asc, fecha_pago asc')
    			   ->fetch();
    
    // Obtiene la moneda oficial
    $moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
    $moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';
    
    // Obtiene la dosificacion del periodo actual
    $dosificacion = $db->from('inv_dosificaciones')->where('fecha_limite', $venta['fecha_limite'])->fetch_first();
    
    // Obtiene los permisos
    $permisos = explode(',', permits);
    
    // Almacena los permisos en variables
    $permiso_editar = in_array('notas_editar', $permisos);
    $permiso_listar = in_array('notas_listar', $permisos);
    $permiso_reimprimir = in_array('notas_obtener', $permisos);
    
    $permiso_guardar_pago = in_array('guardar_pago', $permisos);
    $permiso_eliminar_pago = in_array('eliminar_pago', $permisos);
    $permiso_imprimir_comprobante = in_array('imprimir_comprobante', $permisos);
    
    $NP_generado = generaPago($db, $_user['persona_id'], $venta['id_pago']);
    // echo $NP_generado;
    
    ?>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    
    <div class="panel panel-info">
        <div class="panel-heading" data-formato="<?= strtoupper($formato_textual); ?>" data-venta="<?= $id_venta; ?>" data-servidor="<?= ip_local . 'sistema/nota.php'; ?>">
        	<h3 class="panel-title">
        		<span class="glyphicon glyphicon-option-vertical"></span>
        		<strong>Detalle de la preventa</strong>
        	</h3>
        </div>
    </div>    
        
        	<input id="pago" name="pago" type="hidden" value="<?php echo $venta['id_pago']; ?>">
        	<input id="nro_factura" name="nro_factura" type="hidden" value="<?= $NP_generado ?>">
        	<div class="row">
        		<div class="col-sm-12">
        			<div class="panel panel-primary">
        				<div class="panel-heading">
        					<h3 class="panel-title"><i class="glyphicon glyphicon-list"></i> Detalle de la nota de remisión</h3>
        				</div>
        				<div class="panel-body">
        					<?php if ($detalles) { ?>
        					<div class="table-responsive">
        						<table id="table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
        							<thead>
        								<tr class="active">
        									<th class="text-nowrap">#</th>
        									<th class="text-nowrap">Código</th>
        									<th class="text-nowrap">Nombre</th>
        									<th class="text-nowrap">Unidad</th>
        									<th class="text-nowrap">Cantidad</th>
        									<th class="text-nowrap">Precio <?= escape($moneda); ?></th>
        									<th class="text-nowrap hidden">Descuento (%)</th>
        									<th class="text-nowrap">Importe <?= escape($moneda); ?></th>
        								</tr>
        							</thead>
        							<tbody>
        								<?php $total = 0; ?>
        								<?php foreach ($detalles as $nro => $detalle) { ?>
        								<tr>
        									<?php $cantidad = escape($detalle['cantidad']/cantidad_unidad($db,$detalle['producto_id'],$detalle['unidad_id'])); ?>
        									<?php $precio = escape($detalle['precio']); ?>
        									<?php $importe = $cantidad * $precio; ?>
        									<?php $total = $total + $importe; ?>
        									<th class="text-nowrap"><?= $nro + 1; ?></th>
        									<td class="text-nowrap"><?= escape($detalle['codigo']); ?></td>
        									<td><?= escape($detalle['nombre_factura']); ?></td>
        									<td class="text-nowrap"><?= escape($detalle['unidad']) . " (" . escape($detalle['tamanio']) . ")"; ?></td>
        									<td class="text-nowrap text-right"><?= $cantidad; ?></td>
        									<td class="text-nowrap text-right"><?= number_format($precio, 2, ',', '.'); ?></td>
        									<td class="text-nowrap text-right hidden"><?= number_format($venta['descuento'], 2, ',', '.'); ?></td>
        									<td class="text-nowrap text-right"><?= number_format($importe, 2, ',', '.'); ?></td>
        								</tr>
        								<?php } ?>
        							</tbody>
        							<tfoot>
        								<?php
        								if($total > 0){
        									$descuento = ($total * $venta['descuento']) / 100 ;
        									$descuento_total = $total - $descuento;
        								}else{
        									$descuento_total = $total;
        								}
        								if($venta['descuento'] > 0.00){
        								?>
        									<tr class="active">
        										<th class="text-nowrap text-right" colspan="6">IMPORTE TOTAL <?= escape($moneda); ?></th>
        										<th class="text-nowrap text-right"><?= number_format($total, 2, ',', '.'); ?></th>
        									</tr>
        									<tr class="active">
        										<th class="text-nowrap text-right" colspan="6">DESCUENTO DEL <?= escape(number_format($venta['descuento']), 0) . " %"?></th>
        										<th class="text-nowrap text-right"><?= escape( number_format($descuento, 2, ',', '.')) . ""?></th>
        									</tr>
        									<tr class="active">
        										<th class="text-nowrap text-right" colspan="6">IMPORTE TOTAL CON DESCUENTO<?= escape($moneda); ?></th>
        										<th class="text-nowrap text-right"><?= number_format($descuento_total, 2, ',', '.')?></th></th>
        									</tr>
        								<?php
        								}else{
        								?>
        									<tr class="active">
        										<th class="text-nowrap text-right" colspan="6">IMPORTE TOTAL <?= escape($moneda); ?></th>
        										<th class="text-nowrap text-right"><?= number_format($descuento_total, 2, ',', '.'); ?></th>
        									</tr>
        								<?php
        								}
        								?>
        								<input id="totalProducto" type='hidden' value="<?= $descuento_total ?>">
        							</tfoot>
        
        						</table>
        					</div>
        					<?php } else { ?>
        					<div class="alert alert-danger">
        						<strong>Advertencia!</strong>
        						<p>Esta nota de remisión no tiene detalle, es muy importante que todos los egresos cuenten con un detalle que especifique la operación realizada.</p>
        					</div>
        					<?php } ?>
        				</div>
        			</div>
        			
        
        			
        			<?php if (escape($venta['plan_de_pagos'])=="si"){ ?>
        			<div class="panel panel-primary">
        				<div class="panel-heading">
        					<h3 class="panel-title"><i class="glyphicon glyphicon-list"></i> Detalle del las cuotas</h3>
        				</div>
        				<div class="panel-body">
        					<?php if ($detallesCuota) { ?>
        					<div class="table-responsive">
        						<table id="cuotas_table" class="table table-bordered table-condensed table-restructured table-striped table-hover">
        							<thead>
        								<tr class="active">
        									<th class="text-nowrap">#</th>
        									<th class="text-nowrap">Descripción</th>
        									<th class="text-nowrap">Fecha Programada</th>
        									<th class="text-nowrap">Monto <?= escape($moneda); ?></th>
        									<th class="text-nowrap">Estado</th>
        									<?php if($permiso_guardar_pago){ ?>
        									<th class="text-nowrap">Guardar</th>
        									<?php } ?>
        									<?php if($permiso_imprimir_comprobante){ ?>
        									<th class="text-nowrap">Comprobante</th>
        									<?php } ?>
        								</tr>
        							</thead>
        							<tbody>
        								<?php $total = 0; ?>
        								<?php foreach ($detallesCuota as $nro => $detalle) { 
        									$total=$total+$detalle['monto'];
        									$i=$nro + 1
        								?>
        								<tr>
        									<td class="text-nowrap">
        										<div data-cuota="<?= $i ?>" class="cuota_div">
        										<?= $i; ?>
        										</div>
        									</td>
        									<td class="text-nowrap detalle">
        										<div data-cuota="<?= $i ?>" class="cuota_div">
        										<?php echo "Cuota #".($i); ?>
        										<div>
        									</td>
        									<td class="text-nowrap text-center">
        										<div data-cuota="<?= $i ?>" class="cuota_div">
        											<div class="col-md-12">
        												<?= date_decode($detalle['fecha'], $_institution['formato']); ?>
        											</div>
        										</div>
        									</td>
        									<td class="text-nowrap text-right">
        										<div data-cuota="<?= $i ?>" class="cuota_div">
        												<?= number_format($detalle['monto'], 2, ',', '.'); ?>
        										</div>
        									</td>
        									
        									<td class="text-nowrap text-center">
        										<div data-cuota="<?= $i ?>" class="cuota_div">
        										<?php 
        										if($detalle['estado']==0){
        										?>
        										    Pendiente
        										<?php
        										}else{
        										?>
        											Cancelado
        										<?php
        										}
        										?>
        										</div>
        									</td>
        								</tr>
        								<?php } ?>
        							</tbody>
        							<tfoot>
        								<tr class="active">
        									<th class="text-nowrap text-right" colspan="3">Importe total <?= escape($moneda); ?></th>
        									<th class="text-nowrap text-right" id="total_cuotas"><?= number_format($total, 2, '.', ','); ?></th>
        									<th class="text-nowrap" colspan="4">
        										<span id="conclusion" class="text-danger"></span>
        									</th>
        								</tr>
        							</tfoot>
        						</table>
        
        					</div>
        					<?php } else { ?>
        					<div class="alert alert-danger">
        						<strong>Advertencia!</strong>
        						<p>Este ingreso no tiene detalle, es muy importante que todos los ingresos cuenten con un detalle de la compra.</p>
        					</div>
        					<?php } ?>
        				</div>
        			</div>
        			<?php } ?>
        
        
        			<div class="panel panel-primary">
        				<div class="panel-heading">
        					<h3 class="panel-title"><i class="glyphicon glyphicon-log-out"></i> Información de la nota de remisión</h3>
        				</div>
        				<div class="panel-body">
        					<div class="form-horizontal">
        						<div class="form-group">
        							<label class="col-md-3 control-label">Fecha y hora:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape(date_decode($venta['fecha_egreso'], $_institution['formato'])); ?> <small class="text-success"><?= escape($venta['hora_egreso']); ?></small></p>
        							</div>
        						</div>
        						<div class="form-group">
        							<label class="col-md-3 control-label">Cliente:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape($venta['cliente']); ?></p>
        							</div>
        						</div>
        						<div class="form-group">
        							<label class="col-md-3 control-label">NIT / CI:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape($venta['nit_ci']); ?></p>
        							</div>
        						</div>
        						<div class="form-group">
        							<label class="col-md-3 control-label">Tipo de egreso:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape($venta['tipo']); ?></p>
        							</div>
        						</div>
        						<div class="form-group">
        							<label class="col-md-3 control-label">Número de Nota:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape($venta['nro_nota']); ?></p>
        							</div>
        						</div>
        						<div class="form-group">
        							<label class="col-md-3 control-label">Observación:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape($venta['descripcion_venta']); ?></p>
        							</div>
        						</div>
        						<!--<div class="form-group">-->
        						<!--	<label class="col-md-3 control-label">Descripción:</label>-->
        						<!--	<div class="col-md-9">-->
        						<!--		<p class="form-control-static"><?= escape($venta['descripcion']); ?></p>-->
        						<!--	</div>-->
        						<!--</div>-->
        						<div class="form-group">
        							<label class="col-md-3 control-label">Monto total:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape(number_format($venta['monto_total'],2,',','.')); ?></p>
        							</div>
        						</div>
        						<div class="form-group">
        							<label class="col-md-3 control-label">Código de control:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape($venta['codigo_control']); ?></p>
        							</div>
        						</div>
        
        						<div class="form-group">
        							<label class="col-md-3 control-label">Tipo de Pago:</label>
        							<div class="col-md-9">
        								<?php if (escape($venta['plan_de_pagos'])=="si"){ ?>
        									<p class="form-control-static">Plan de Pagos</p>
        								<?php }else{ ?>
        									<p class="form-control-static">Contado</p>
        								<?php } ?>
        							</div>
        						</div>
        						
        						<div class="form-group">
        							<label class="col-md-3 control-label">Número de registros:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape($venta['nro_registros']); ?></p>
        							</div>
        						</div>
        						<div class="form-group">
        							<label class="col-md-3 control-label">Almacén:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape($venta['almacen']); ?></p>
        							</div>
        						</div>
        						<div class="form-group">
        							<label class="col-md-3 control-label">Vendedor:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape($venta['nombresv'] . ' ' . $venta['paternov'] . ' ' . $venta['maternov']); ?></p>
        							</div>
        						</div>
        						<div class="form-group">
        							<label class="col-md-3 control-label">Operador:</label>
        							<div class="col-md-9">
        								<p class="form-control-static"><?= escape($venta['nombres'] . ' ' . $venta['paterno'] . ' ' . $venta['materno']); ?></p>
        							</div>
        						</div>
        					</div>
        				</div>
        			</div>
        		</div>
        	</div>
<?php
}
?>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>