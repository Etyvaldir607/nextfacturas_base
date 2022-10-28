<?php
// Obtiene el id_ingreso
$id_ingreso = (sizeof($params) > 0) ? $params[0] : 0;

$tipo_ingreso = (sizeof($params) > 2) ? $params[2] : 0;

// Obtiene los ingreso
switch($tipo_ingreso){
    case 3: break;
    case 2: 
            $ingreso = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, fi.almacen as almacen_s') 
            			  ->from('inv_ingresos i')
            			  ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
            			  ->join('inv_almacenes fi', 'i.almacen_id_s = fi.id_almacen', 'left')
            			  ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
            			  ->where('importacion_id', $id_ingreso)
            			  ->fetch_first();
            
            $id_importacion=$id_ingreso;
            break;
    case 5: 
            $ingreso = $db->select('i.*, e.nombres, e.paterno, e.materno, SUM(pd.monto) as monto_total') 
            			  ->from('inv_ingresos i')
            			  ->join('inv_pagos p', 'p.tipo="Rendicion" AND p.movimiento_id = i.id_ingreso', 'left')
            			  ->join('inv_pagos_rendicion pd', 'pd.ingreso_id = i.id_ingreso', 'left')
            			  ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
            			  ->where('id_ingreso', $id_ingreso)
            			  ->fetch_first();
        
            $id_importacion=$ingreso['importacion_id'];
            break;
    default:    
            $ingreso = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno, fi.almacen as almacen_s') 
            			  ->from('inv_ingresos i')
            			  ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
            			  ->join('inv_almacenes fi', 'i.almacen_id_s = fi.id_almacen', 'left')
            			  ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
            			  ->where('id_ingreso', $id_ingreso)
            			  ->fetch_first();
        
            $id_importacion=$ingreso['importacion_id'];
            break;
}

if($tipo_ingreso==2 || $ingreso['tipo'] == 'Importacion'){
	$importacion = $db->select('im.*, a.almacen, p.proveedor')
					->from('inv_importacion as im')
					->join('inv_almacenes a', 'im.almacen_id = a.id_almacen', 'left')
					->join('inv_proveedores p', 'im.id_proveedor = p.id_proveedor', 'left')
					->where('id_importacion', $id_importacion)
					->fetch_first();
}

if ($ingreso['tipo'] == 'Devolucion') {
	$e_array = $db->select('id_egreso')
	              ->from('inv_egresos')
	              ->where('ingreso_id', $ingreso['id_ingreso'])
	              ->fetch();
	$ides = [];
	foreach ($e_array as $key => $ea) {
		array_push($ides,  $ea['id_egreso']);
	}
}

// Verifica si existe el ingreso
/*if (!$ingreso) {
	// Error 404
	require_once not_found();
	exit;
}*/

// Obtiene los detalles
switch($tipo_ingreso){
    case 3: break;
    
    case 2: 
        //if($importacion['estado']=='activo'){
            $campo='cantidad';    
        //}
        //else{
            //$campo='cantidad_recibida';    
        //}
        
        $detalles = $db->select('d.*, d.fechav as vencimiento, precio_ingreso as costo, "false" as factura_v,
                                 d.id_tmp_ingreso_detalle as id_detalle, p.codigo, p.nombre, p.nombre_factura, '.$campo.' as cantidad')
        			   ->from('tmp_ingreso_detalle d')
        			   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
        			   ->where('d.importacion_id', $id_ingreso)
        			   ->group_by('d.producto_id, d.precio_salida, d.lote, d.fechav')
        			   ->order_by('id_tmp_ingreso_detalle asc')
        			   ->fetch();
        break;			   
    
    default:
        $detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura, SUM(cantidad)as cantidad')
        			   ->from('inv_ingresos_detalles d')
        			   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
        			   ->where('d.ingreso_id', $id_ingreso)
        			   ->group_by('producto_id, costo, lote, vencimiento')
        			   ->order_by('id_detalle asc')
        			   ->fetch();
        break;			   
}

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')
			 ->where('oficial', 'S')
			 ->fetch_first();

$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_crear = in_array('crear', $permisos);
$permiso_eliminar = in_array('eliminar', $permisos);
$permiso_imprimir = in_array('imprimir', $permisos);
$permiso_listar = in_array('listar', $permisos);
$permiso_suprimir = in_array('suprimir', $permisos);

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<b>Ver ingreso de mercaderia</b>
	</h3>
</div>
<div class="panel-body">
	<?php if ($permiso_crear || $permiso_eliminar || $permiso_imprimir || $permiso_listar) { ?>
	<div class="row">
		<div class="col-sm-7 col-md-6 hidden-xs">
			<div class="text-label">Para realizar una acción hacer clic en los botones:</div>
		</div>
		<div class="col-xs-12 col-sm-5 col-md-6 text-right">
			<?php if ($permiso_crear && $tipo_ingreso==0 && false) { ?>
			<a href="?/ingresos/crear" class="btn btn-success">
				<span class="glyphicon glyphicon-plus"></span>
				<span class="hidden-xs hidden-sm">Nuevo</span>
			</a>
			<?php } ?>
			<?php if ($permiso_eliminar && $tipo_ingreso==0 && false) { ?>
			<a href="?/ingresos/eliminar/<?= $ingreso['id_ingreso']; ?>" class="btn btn-danger" data-eliminar="true">
				<span class="glyphicon glyphicon-trash"></span>
				<span class="hidden-xs hidden-sm">Eliminar</span>
			</a>
			<?php } ?>
			<?php if ($permiso_imprimir && $tipo_ingreso==0 && false) { ?>
			<a href="?/ingresos/imprimir/<?= $ingreso['id_ingreso']; ?>" target="_blank" class="btn btn-info">
				<span class="glyphicon glyphicon-print"></span>
				<span class="hidden-xs hidden-sm">Imprimir</span>
			</a>
			<?php } ?>
			<?php if ($permiso_listar && $tipo_ingreso==0 && false) { ?>
			<a href="?/ingresos/listar" class="btn btn-primary">
				<span class="glyphicon glyphicon-list-alt"></span>
				<span class="hidden-xs hidden-sm <?= ($permiso_imprimir) ? 'hidden-md' : ''; ?>">Listado</span>
			</a>
			<?php } ?>
		</div>
	</div>
	<hr>
	<?php } ?>
	
	<?php
	if($tipo_ingreso!=3 && $tipo_ingreso!=5){
    ?>
	<div class="row">
		<div class="col-sm-12 col-sm-offset-0">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><span class="glyphicon glyphicon-list"></span> Detalle del ingreso</h3>
				</div>
				<div class="panel-body">
					<?php if ($detalles) { ?>
					<div class="table-responsive">
						<table id="table" class="table table-bordered table-condensed table-striped table-hover">
							<thead>
								<tr class="active">
									<th class="text-nowrap">#</th>
									<th class="text-nowrap">Código</th>
									<th class="text-nowrap">Nombre</th>
									<th class="text-nowrap">Factura</th>
									<th class="text-nowrap">Lote</th>
                                    <th class="text-nowrap">Cantidad</th>
                                    <th class="text-nowrap">F. vencimiento</th>
                                    <!-- <th class="text-nowrap">Nro DUI</th>
                                    <th class="text-nowrap">Contenedor</th> -->
									<th class="text-nowrap">Costo <?= escape($moneda); ?></th>
									<th class="text-nowrap">Importe <?= escape($moneda); ?></th>
									<!--th class="text-nowrap">Importe Facturado <?= escape($moneda); ?></th-->
									<!-- <?php // if ($permiso_suprimir) { ?> -->
									<?php if ($ingreso['tipo'] == 'Devolucion') { ?>
									<!--<th class="text-nowrap">Opciones</th>-->
									<?php } ?>
								</tr>
							</thead>
							<tbody>
								<?php $total = 0; $totalF = 0; ?>
								<?php foreach ($detalles as $nro => $detalle) { ?>
								<tr>
									<?php $cantidad = escape($detalle['cantidad']); ?>
									<?php $costo = escape($detalle['costo']); ?>
									<?php $importe = $cantidad * $costo; ?>
									<?php $total = $total + $importe; ?>

									<?php
										if ($detalle['factura_v'] == true) {
											$importeF = (($cantidad * $costo)-((($cantidad * $costo)/100)*13));
											$totalF = ($totalF+$importeF);
										} else  {
											$importeF = $importe;
											$totalF = $totalF + $importeF;
										}
									?>


									<th class="text-nowrap"><?= $nro + 1; ?></th>
									<td class="text-nowrap"><?= escape($detalle['codigo']); ?></td>
									<td><?= escape($detalle['nombre_factura']); ?></td>
									<td class="text-nowrap <?= escape(($detalle['factura_v'] == true) ? 'bg-info' : 'bg-danger' ); ?>"><?= escape(($detalle['factura_v'] == true) ? 'SI' : 'NO' ); ?></td>
									<td class="text-nowrap"><?= escape($detalle['lote']); ?></td>
                                    <td class="text-nowrap text-right"><?= number_format($cantidad, 0, '', '.'); ?></td>
                                    <td class="text-nowrap text-right"><?= date_decode($detalle['vencimiento'], $_institution['formato']); ?></td>
                                    <td class="text-nowrap text-right"><?= number_format($costo, 2, ',', '.'); ?></td>
									<td class="text-nowrap text-right"><?= number_format($importe, 2, ',', '.'); ?></td>
									<!--td class="text-nowrap text-right"><?= number_format($importeF, 2, ',', '.'); ?></td-->
									
										<?php if ($ingreso['tipo'] == 'Devolucion' && false) { ?>
											<td class="text-nowrap">
											<?php if(count($e_array) > 0){
												$e_cantidades = $db->select('IFNULL(SUM(cantidad) ,0) as cantidad, lote, id_detalle')->from('inv_egresos_detalles')->where_in('egreso_id', $ides)->where('producto_id', $detalle['producto_id'])->where('detalle_ingreso_id', $detalle['id_detalle'])->fetch_first(); 
												// echo $db->last_query();
											} else {
												$e_cantidades = array('cantidad' => 0 );
											}
											// echo $db->last_query();
											// echo '<br>' . json_encode($detalle['cantidad']) . ' >>>> ' . $e_cantidades['cantidad'];  die();
											if ($detalle['cantidad'] > $e_cantidades['cantidad']) {?>
												<?php if ($ingreso['tipo_devol'] == 'notas') {?>
													<a href="?/operaciones/notas_reponer/<?= $ingreso['id_ingreso']; ?>/<?= $detalle['id_detalle']; ?>" data-toggle="tooltip" data-title="Reponer devolucion" class="text-danger"><span class="glyphicon glyphicon-transfer"></span></a>
												<?php } ?>
												<?php if ($ingreso['tipo_devol'] == 'preventa') {?>
													<a href="?/operaciones/preventas_reponer/<?= $ingreso['id_ingreso']; ?>/<?= $detalle['id_detalle']; ?>" data-toggle="tooltip" data-title="Reponer devolucion" class="text-danger"><span class="glyphicon glyphicon-transfer"></span></a>
												<?php } ?>
												<?php if ($ingreso['tipo_devol'] == 'factura') {?>
													<a href="?/operaciones/facturas_reponer/<?= $ingreso['id_ingreso']; ?>/<?= $detalle['id_detalle']; ?>" data-toggle="tooltip" data-title="Reponer devolucion" class="text-danger"><span class="glyphicon glyphicon-transfer"></span></a>
												<?php } ?>
											<?php } else {
													echo "<small class='text-success'>Devuelto</small>";
													}
											?>
											</td>
										<?php } ?>
									
								</tr>
								<?php } ?>
							</tbody>
							<tfoot>
								<tr class="active">
									<th class="text-nowrap text-right" colspan="8">Importe total <?= escape($moneda); ?></th>
									<th class="text-nowrap text-right"><?= number_format($total, 2, ',', '.'); ?></th>
									<!--th class="text-nowrap text-right"><?= number_format($totalF, 2, ',', '.'); ?></th-->
									<?php if ($ingreso['tipo'] == 'Devolucion') { ?>
									<!--<th class="text-nowrap">Opciones</th>-->
									<?php } ?>
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
		</div>
	</div>
    <?php
	}
	if($tipo_ingreso==5){
	    // Obtiene los detalles
        $detallesRendicion = $db->select('*')
                			    ->from('inv_pagos_rendicion pr')
                			    ->where('pr.ingreso_id', $id_ingreso)
                			    ->fetch();

    ?>
	<div class="row">
		<div class="col-sm-12 col-sm-offset-0">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><span class="glyphicon glyphicon-list"></span> Detalle</h3>
				</div>
				<div class="panel-body">
					<?php if ($detallesRendicion) { ?>
					<div class="table-responsive">
						<table id="table" class="table table-bordered table-condensed table-striped table-hover">
							<thead>
								<tr class="active">
									<th class="text-nowrap text-center text-middle">Fecha</th>
									<th class="text-nowrap text-center text-middle">Concepto</th>
									<th class="text-nowrap text-center text-middle">Nro. Factura</th>
									<th class="text-nowrap text-center text-middle">Monto <?= escape($moneda); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php $total = 0; 
								foreach ($detallesRendicion as $nro => $detalle) { 
									$total=$total+$detalle['monto'];
									$i=$nro + 1
								?>
								<tr>
									<td class="text-nowrap text-center">
										<div data-cuota="<?= $i ?>" class="cuota_div">
											<div class="col-md-12">
												<?= escape(date_decode($detalle['fecha'], $_institution['formato'])); ?>
											</div>
										</div>
									</td>
									<td class="">
										<div data-cuota="<?= $i ?>" class="cuota_div">
											<div class="col-md-12">
												<?php 
        										    echo $detalle['concepto']; 
    											?>
											</div>
										</div>
									</td>
									<td class="">
										<div data-cuota="<?= $i ?>" class="cuota_div">
											<div class="col-md-12">
												<?php 
        										    echo $detalle['nro_factura']; 
    											?>
											</div>
										</div>
									</td>
									<td class="text-nowrap text-right">
										<div data-cuota="<?= $i ?>" class="cuota_div">
											<div class="col-md-12">
												<?php 
        										    echo number_format($detalle['monto'], 2, ',', '.'); 
    											?>
											</div>
										</div>
									</td>
								</tr>
								<?php } ?>
								<tr>
									<th class="text-nowrap text-center" colspan=3>
										<div>
											<div class="col-md-12">
												TOTAL
											</div>
										</div>
									</th>
									<th class="text-nowrap text-right">
										<div data-cuota="<?= $i ?>" class="cuota_div">
											<div class="col-md-12">
												<?php 
        										    echo number_format($total, 2, ',', '.'); 
    											?>
											</div>
										</div>
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
		</div>
	</div>
    <?php
	}
    ?>
	
    <div class="row">
    	<?php
        if ($ingreso['tipo'] == 'Compra') {
        ?>
		<!--div class="<?= ($ingreso['tipo'] == 'Importacion') ? 'col-md-5 col-sm-offset-1' : 'col-sm-12 col-sm-offset-0' ?>"-->
		<div class="col-sm-12">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-log-in"></i> Información del ingreso</h3>
				</div>
				<div class="panel-body">
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-4 control-label">Número de factura:</label>
							<div class="col-md-8">
								<p class="form-control-static"><b><?= escape($ingreso['nro_factura']); ?></b></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-4 control-label">Fecha de factura:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape(date_decode($ingreso['fecha_factura'], $_institution['formato'])); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-4 control-label">Proveedor:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape($ingreso['nombre_proveedor']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-4 control-label">Tipo de ingreso:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape($ingreso['tipo']); ?></p>
							</div>
						</div>
						<?php if ($ingreso['tipo'] == 'Traspaso') { ?>
							<div class="form-group">
								<label class="col-md-4 control-label">Almacen salida:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape($ingreso['almacen']); ?></p>
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-4 control-label">Almacen destino:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape($ingreso['almacen_s']); ?></p>
								</div>
							</div>
						<?php }	?>

						<div class="form-group">
							<label class="col-md-4 control-label">Descripción:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape($ingreso['descripcion']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-4 control-label">Monto total:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape(number_format($ingreso['monto_total'], 2, ',', '.')); ?></p>
							</div>
						</div>
						<!--div class="form-group">
							<label class="col-md-4 control-label">Descuento:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape(number_format($ingreso['descuento'], 2, ',', '.')); ?> %</p>
							</div>
						</div>
						<?php if ($ingreso['monto_total_descuento']>0) {  
							$descuento= $ingreso['monto_total_descuento'];
						} else {
                            $descuento= $ingreso['monto_total'];
						}?>
						<div class="form-group">
							<label class="col-md-4 control-label">Monto total con Descuento:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape(number_format($descuento, 2, ',', '.')); ?></p>
							</div>
						</div-->
						<div class="form-group">
							<label class="col-md-4 control-label">Número de registros:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape($ingreso['nro_registros']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-4 control-label">Almacén:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape($ingreso['almacen']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-4 control-label">Empleado:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape($ingreso['nombres'] . ' ' . $ingreso['paterno'] . ' ' . $ingreso['materno']); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-4 control-label">Fecha y hora:</label>
							<div class="col-md-8">
								<p class="form-control-static"><?= escape(date_decode($ingreso['fecha_ingreso'], $_institution['formato'])); ?> <small class="text-success"><?= escape($ingreso['hora_ingreso']); ?></small></p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
    	<?php
        }
        ?>
        
        <div class="col-md-12">
    		<?php
            if($tipo_ingreso==2 || $ingreso['tipo'] == 'Importacion'){
            ?>
				<div class="panel panel-primary">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="glyphicon glyphicon-log-in"></i> Información de la importacion</h3>
					</div>
					<div class="panel-body">
						<div class="form-horizontal">
							<div class="form-group">
								<label class="col-md-4 control-label">Almacén:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape($importacion['almacen']); ?></p>
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-4 control-label">Proveedor:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape($importacion['proveedor']); ?></p>
								</div>
							</div>
    						<div class="form-group">
    							<label class="col-md-4 control-label">Número de factura:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><b><?= escape($importacion['nro_factura']); ?></b></p>
    							</div>
    						</div>
							<div class="form-group">
								<label class="col-md-4 control-label">Fecha de factura:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape(date_format(date_create($importacion['fecha_factura']), 'd/m/Y')); ?> </p>
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-4 control-label">Fecha de ingreso:</label>
								<div class="col-md-8">
									<!--p class="form-control-static"><?= escape(date_format(date_create($importacion['fecha_factura']), 'd/m/Y')); ?> </p-->
									<?php if( isset($ingreso['fecha_ingreso']) ){ ?>
									<p class="form-control-static">
									    <?= escape(date_decode($ingreso['fecha_ingreso'], $_institution['formato'])); ?> 
									    <small class="text-success"><?= escape($ingreso['hora_ingreso']); ?></small>
									</p>
									<?php }else{ ?>
									<p class="form-control-static">Aun no ha ingresado a Almacen
									</p>
									<?php } ?>
								</div>
							</div>
							<!--div class="form-group">
								<label class="col-md-4 control-label">Nro. registros:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape($importacion['nro_registros']); ?></p>
								</div>
							</div-->
							<div class="form-group">
								<label class="col-md-4 control-label">Total:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape($importacion['total']); ?></p>
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-4 control-label">Total gastos:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape($importacion['total_gastos']); ?></p>
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-4 control-label">Total costo:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape($importacion['total_costo']); ?></p>
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-4 control-label"><u>Total neto:</u></label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape( number_format($importacion['total'] + $importacion['total_costo'] ,2)); ?> <small class="text-info">(Total + Total costo)</small></p>
								</div>
							</div>
							<!--div class="form-group">
								<label class="col-md-4 control-label">Fecha de registro:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape(date_format(date_create($importacion['fecha_inicio']), 'd/m/Y')); ?>  <small class="text-success"> <?= escape(date_format(date_create($importacion['fecha_inicio']), 'H:i:s')); ?></small></p>
								</div>
							</div>
							<!--div class="form-group">
								<label class="col-md-4 control-label">Fecha de finalización:</label>
								<div class="col-md-8">
								<p class="form-control-static"><?= escape(date_format(date_create($importacion['fecha_final']), 'd/m/Y')); ?>  <small class="text-success"> <?= escape(date_format(date_create($importacion['fecha_final']), 'H:i:s')); ?></small></p>
								</div>
							</div-->
							<div class="form-group">
								<label class="col-md-4 control-label">Descripción:</label>
								<div class="col-md-8">
									<p class="form-control-static"><?= escape($importacion['descripcion']); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			
				<div class="panel panel-primary">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="glyphicon glyphicon-log-in"></i> Listado de gastos</h3>
					</div>
					<div class="panel-body">
						<div class="table-responsive">
							<table class="table table-bordered">
								<?php
								//DETALLE DE LOS GASTOS
									$Gastos='';
									$Consulta=$db->query("SELECT ig.id_importacion_gasto,ig.nombre,ig.codigo,ig.fecha,e.nombres,e.paterno,e.materno
															FROM inv_importacion_gasto AS ig
															LEFT JOIN sys_empleados AS e ON ig.empleado_id=e.id_empleado
															WHERE ig.importacion_id='{$importacion["id_importacion"]}'")->fetch();

									foreach($Consulta as $Fila=>$Dato):
										$Dato_fecha=explode(" ",$Dato['fecha']);
										
										$Dato_fecha1=escape(date_format(date_create($Dato_fecha[0]), 'd/m/Y'))." ".escape(date_format(date_create($Dato_fecha[1]), 'H:i:s')); 
										
										$Gastos.="<thead><tr class='active'>
												<th>{$Dato['nombre']}</th>
												<th>{$Dato['codigo']}</th>
												<th>{$Dato_fecha1}</th>
												<th colspan=\"2\">{$Dato['nombres']} {$Dato['paterno']} {$Dato['materno']}</th>
											</tr>
											<tr class='active'>
												<th>GASTO</th>
												<th>FACTURA</th>
												<th>COSTO AÑADIDO (%)</th>
												<th>IMPORTE {$moneda}</th>
												<th>COSTO AL PRODUCTO {$moneda}</th>
											</tr></thead><tbody>";
										$IdImportacionGasto=$Dato['id_importacion_gasto'];
										$SubConsulta=$db->query("SELECT gasto,factura,costo_anadido,costo
																FROM inv_importacion_gasto_detalle
																WHERE importacion_gasto_id='{$IdImportacionGasto}'")->fetch();
										$Total1=0;
										$Total2=0;
										foreach($SubConsulta as $Nro=>$SubDato):
											$CostoAlProducto=($SubDato['costo_anadido']*0.01)*$SubDato['costo'];
											$CostoAlProducto=round($CostoAlProducto,2);
											$Gastos.="<tr>
													<td>{$SubDato['gasto']}</td>
													<td>{$SubDato['factura']}</td>
													<td class='text-right'>".number_format($SubDato['costo_anadido'], 2, ',', '.')."</td>
													<td class='text-right'>".number_format($SubDato['costo'], 2, ',', '.')."</td>
													<td class='text-right'>".number_format($CostoAlProducto, 2, ',', '.')."</td>
												</tr>";
											$Total1=$Total1+$SubDato['costo'];
											$Total2=$Total2+$CostoAlProducto;
										endforeach;
										$Gastos.="<tr class='active'>
													<td colspan=\"3\"></td>
													<td class='text-right'>".number_format($Total1, 2, ',', '.')."</td>
													<td class='text-right'>".number_format($Total2, 2, ',', '.')."</td>
												</tr></tbody>";
									endforeach;

									echo $Gastos;
								?>
							</table>
						</div>
					</div>
				</div>
			<?php } ?>	
			
			<?php
            if($tipo_ingreso==3){
            ?>
				<div class="panel panel-primary">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="glyphicon glyphicon-log-in"></i> Informacion del gastos</h3>
					</div>
					<div class="panel-body">
						<div class="table-responsive">
								<?php
									$Gastos='';
									$Dato=$db->query("SELECT ig.id_importacion_gasto,ig.nombre,ig.codigo,
									                        DATE(ig.fecha) as fecha_gasto,
									                        TIME(ig.fecha) as hora_gasto,
									                        e.nombres,e.paterno,e.materno, p.proveedor
															FROM inv_importacion_gasto AS ig
															LEFT JOIN sys_empleados AS e ON ig.empleado_id=e.id_empleado
															LEFT JOIN inv_proveedores AS p ON ig.proveedor_id=p.id_proveedor
															WHERE id_importacion_gasto='{$id_ingreso}'")->fetch_first();

								    echo "<table class='table table-bordered'>
										<tr class='active'>
											<th>GASTO</th>
											<th>FACTURA</th>
											<th>COSTO AÑADIDO (%)</th>
											<th>IMPORTE {$moneda}</th>
											<th>COSTO AL PRODUCTO {$moneda}</th>
										</tr></thead><tbody>";
									
									$IdImportacionGasto=$Dato['id_importacion_gasto'];
									$SubConsulta=$db->query("SELECT gasto,factura,costo_anadido,costo
															FROM inv_importacion_gasto_detalle
															WHERE importacion_gasto_id='{$IdImportacionGasto}'")->fetch();
									$Total1=0;
									$Total2=0;
									foreach($SubConsulta as $Nro=>$SubDato):
										$CostoAlProducto=($SubDato['costo_anadido']*0.01)*$SubDato['costo'];
										$CostoAlProducto=round($CostoAlProducto,2);
										$Gastos.="<tr>
												<td>{$SubDato['gasto']}</td>
												<td>{$SubDato['factura']}</td>
												<td align='right'>".number_format($SubDato['costo_anadido'],2,'.',',')."</td>
												<td align='right'>".number_format($SubDato['costo'],2,'.',',')."</td>
												<td align='right'>".number_format($CostoAlProducto,2,'.',',')."</td>
											</tr>";
										$Total1=$Total1+$SubDato['costo'];
										$Total2=$Total2+$CostoAlProducto;
									endforeach;
									$Gastos.="<tr class='active'>
												<td colspan=\"3\"></td>
												<td align='right'>".number_format($Total1,2,'.',',')."</td>
												<td align='right'>".number_format($Total2,2,'.',',')."</td>
											</tr></tbody>";
								
									echo $Gastos;
								?>	
							</table>
							<div class="panel-body">
        						<div class="form-horizontal">
        						    <div class="form-group">
        								<label class="col-md-4 control-label">Proveedor:</label>
        								<div class="col-md-8">
        									<p class="form-control-static"><?= $Dato['proveedor']; ?></p>
        								</div>
        							</div>
								    <div class="form-group">
        								<label class="col-md-4 control-label">Detalle:</label>
        								<div class="col-md-8">
        									<p class="form-control-static"><?= $Dato['nombre']; ?></p>
        								</div>
        							</div>
        						    <div class="form-group">
        								<label class="col-md-4 control-label">Nro Factura:</label>
        								<div class="col-md-8">
        									<p class="form-control-static"><?= $Dato['codigo']; ?></p>
        								</div>
        							</div>
        						    <div class="form-group">
        								<label class="col-md-4 control-label">Fecha:</label>
        								<div class="col-md-8">
        									<p class="form-control-static"><?= escape(date_format(date_create($Dato['fecha_gasto']), 'd/m/Y')); ?>  <small class="text-success"> <?= escape(date_format(date_create($Dato['fecha']), 'H:i:s')); ?></small></p>
        								</div>
        							</div>
        						    <div class="form-group">
        								<label class="col-md-4 control-label">Empleado:</label>
        								<div class="col-md-8">
        									<p class="form-control-static"><?= $Dato['nombres']." ".$Dato['paterno']." ".$Dato['materno']; ?></p>
        								</div>
        							</div>
        						</div>
        					</div>	
						</div>
					</div>
				</div>
			<?php } ?>	

        	<?php
            if ($ingreso['tipo'] == 'Rendicion') {
            ?>
    		<div class="col-sm-12">
    			<div class="panel panel-primary">
    				<div class="panel-heading">
    					<h3 class="panel-title"><i class="glyphicon glyphicon-log-in"></i> Información del ingreso</h3>
    				</div>
    				<div class="panel-body">
    					<div class="form-horizontal">
    						<div class="form-group">
    							<label class="col-md-4 control-label">Número de factura:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><b><?= escape($ingreso['nro_factura']); ?></b></p>
    							</div>
    						</div>
    						<div class="form-group">
    							<label class="col-md-4 control-label">Fecha de factura:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape(date_decode($ingreso['fecha_factura'], $_institution['formato'])); ?></p>
    							</div>
    						</div>
    						<div class="form-group">
    							<label class="col-md-4 control-label">Proveedor:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape($ingreso['nombre_proveedor']); ?></p>
    							</div>
    						</div>
    						<div class="form-group">
    							<label class="col-md-4 control-label">Tipo de ingreso:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape($ingreso['tipo']); ?></p>
    							</div>
    						</div>
    						<?php if ($ingreso['tipo'] == 'Traspaso') { ?>
    							<div class="form-group">
    								<label class="col-md-4 control-label">Almacen salida:</label>
    								<div class="col-md-8">
    									<p class="form-control-static"><?= escape($ingreso['almacen']); ?></p>
    								</div>
    							</div>
    							<div class="form-group">
    								<label class="col-md-4 control-label">Almacen destino:</label>
    								<div class="col-md-8">
    									<p class="form-control-static"><?= escape($ingreso['almacen_s']); ?></p>
    								</div>
    							</div>
    						<?php }	?>
    
    						<div class="form-group">
    							<label class="col-md-4 control-label">Descripción:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape($ingreso['descripcion']); ?></p>
    							</div>
    						</div>
    						<div class="form-group">
    							<label class="col-md-4 control-label">Monto total:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape(number_format($ingreso['monto_total'], 2, ',', '.')); ?></p>
    							</div>
    						</div>
    						<!--div class="form-group">
    							<label class="col-md-4 control-label">Descuento:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape(number_format($ingreso['descuento'], 2, ',', '.')); ?> %</p>
    							</div>
    						</div>
    						<?php if ($ingreso['monto_total_descuento']>0) {  
    							$descuento= $ingreso['monto_total_descuento'];
    						} else {
                                $descuento= $ingreso['monto_total'];
    						}?>
    						<div class="form-group">
    							<label class="col-md-4 control-label">Monto total con Descuento:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape(number_format($descuento, 2, ',', '.')); ?></p>
    							</div>
    						</div-->
    						<div class="form-group">
    							<label class="col-md-4 control-label">Número de registros:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape($ingreso['nro_registros']); ?></p>
    							</div>
    						</div>
    						<div class="form-group">
    							<label class="col-md-4 control-label">Almacén:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape($ingreso['almacen']); ?></p>
    							</div>
    						</div>
    						<div class="form-group">
    							<label class="col-md-4 control-label">Empleado:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape($ingreso['nombres'] . ' ' . $ingreso['paterno'] . ' ' . $ingreso['materno']); ?></p>
    							</div>
    						</div>
    						<div class="form-group">
    							<label class="col-md-4 control-label">Fecha y hora:</label>
    							<div class="col-md-8">
    								<p class="form-control-static"><?= escape(date_decode($ingreso['fecha_ingreso'], $_institution['formato'])); ?> <small class="text-success"><?= escape($ingreso['hora_ingreso']); ?></small></p>
    							</div>
    						</div>
    					</div>
    				</div>
    			</div>
    		</div>
        	<?php
            }
            ?>
		</div>
	</div>
</div>
<script>
$(function () {

	<?php if (isset($_SESSION['imprimir'])) { ?>
		$.open('?/notas/imprimir/<?= $_SESSION['imprimir'] ?>', true);
		// window.location.reload();
		<?php unset($_SESSION['imprimir']); ?>
	<?php } ?>

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
</script>
<?php require_once show_template('footer-advanced'); ?>