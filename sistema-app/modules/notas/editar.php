<?php
$formato_textual = get_date_textual($_institution['formato']);

$id_egreso_ext=$params[0];

$egresoX=$db->query("select i.*
                     from inv_egresos i 
                     WHERE id_egreso='".$params[0]."'")->fetch_first();

$asignacion_clienteX=$db->query("select ac.*
                                 from inv_asignaciones_clientes ac 
                                 WHERE egreso_id='".$params[0]."'
                                 ")->fetch_first();

$sw_asignacion_clienteX=false;
if($asignacion_clienteX){
    if($asignacion_clienteX['nro_salida']<=0){
        $sw_asignacion_clienteX=true;
    }
}else{
    $sw_asignacion_clienteX=true;
}

$detallesX = $db->query("SELECT d.*, SUM(d.cantidad) as cantidad, p.*, u.unidad, IFNULL(id_promocion_precio,0)as id_promocion_precio
						 FROM inv_egresos_detalles AS d
						 LEFT JOIN inv_productos p ON id_producto=producto_id
						 LEFT JOIN inv_unidades u ON u.id_unidad = p.unidad_id
						 LEFT JOIN inv_promocion_precios pp on pp.producto_id=d.producto_id AND pp.lote=d.lote AND pp.vencimiento=d.vencimiento
				         WHERE egreso_id='".$egresoX['id_egreso']."'
				         GROUP BY precio, lote, vencimiento 
				        ")->fetch();

$clienteX = $db->query("SELECT *
						FROM inv_clientes AS c
						LEFT JOIN inv_ciudades as ci ON ci.id_ciudad = c.ciudad_id
						WHERE id_cliente='".$egresoX['cliente_id']."'
					  ")->fetch_first();

$id_almacen=$egresoX['almacen_id'];

$almacen = $db->from('inv_almacenes')->where('id_almacen=', $id_almacen)->fetch_first();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

$usuario = $_user['persona_id'];

$clientes = $db->query("SELECT *
						FROM inv_clientes AS c
						LEFT JOIN inv_clientes_grupos AS cg ON c.cliente_grupo_id=cg.id_cliente_grupo
                        LEFT JOIN inv_ciudades as ci ON ci.id_ciudad = c.ciudad_id
						LEFT JOIN sys_supervisor ss ON cg.id_cliente_grupo=ss.cliente_grupo_id
                                
						WHERE 
						    (
            			        (cg.vendedor_id='".$_user['persona_id']."' AND '".$_user['rol_id']."' = 15)
            			        OR 
            			        (ss.user_ids='".$_user['id_user']."' AND '".$_user['rol_id']."' = 14)
            			        OR 
            			        '".$_user['rol_id']."' = 1
            			    )
						ORDER BY c.cliente ASC,c.nit ASC")->fetch();

$productos = $db->query("SELECT p.id_producto, p.asignacion_rol, p.descuento ,p.promocion,
						z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2,
						p.descripcion,p.imagen,p.codigo,p.nombre_factura as nombre,p.nombre_factura,p.cantidad_minima,p.precio_actual,
						IFNULL(e.cantidad_ingresos, 0) AS cantidad_ingresos, IFNULL(s.cantidad_egresos, 0) AS cantidad_egresos,
						u.unidad, u.sigla, c.categoria, e.vencimiento, e.lote,e.id_detalle, '' as id_detalle_productos, e.id_promocion_precio
						FROM inv_productos p
						
						LEFT JOIN (
							SELECT d.producto_id, SUM(d.cantidad) AS cantidad_ingresos, d.vencimiento, d.lote, d.id_detalle, IFNULL(id_promocion_precio,0)as id_promocion_precio
							FROM inv_ingresos_detalles d
							LEFT JOIN inv_ingresos i ON i.id_ingreso = d.ingreso_id
							left join inv_promocion_precios pp on pp.producto_id=d.producto_id AND pp.lote=d.lote AND pp.vencimiento=d.vencimiento
                            WHERE i.almacen_id = '$id_almacen'
							GROUP BY d.producto_id, d.vencimiento, d.lote
						) AS e ON e.producto_id = p.id_producto
						
						LEFT JOIN (
							SELECT d.producto_id, SUM(IF(e.tipo = 'Preventa' && e.preventa = 'habilitado', d.cantidad, 
							        IF(e.tipo='No venta' && e.estadoe = 4, d.cantidad, IF(e.tipo NOT IN ('Preventa', 'No venta'), d.cantidad, 0)))) AS cantidad_egresos, 
							        d.lote, d.vencimiento
							FROM inv_egresos_detalles d
							LEFT JOIN inv_egresos e ON e.id_egreso = d.egreso_id
							WHERE e.almacen_id = '$id_almacen' AND id_egreso!='$id_egreso_ext'
							GROUP BY d.producto_id,d.vencimiento, d.lote
						) AS s ON s.producto_id = p.id_producto AND s.lote = e.lote AND s.vencimiento = e.vencimiento

						LEFT JOIN inv_unidades u ON u.id_unidad = p.unidad_id
				LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id
				LEFT JOIN (
					SELECT w.producto_id,
						GROUP_CONCAT(w.id_asignacion SEPARATOR '|') AS id_asignacion,
						GROUP_CONCAT(w.unidad_id SEPARATOR '|') AS unidad_id,
						GROUP_CONCAT(w.cantidad_unidad,')',w.unidad,':',w.otro_precio SEPARATOR '&') AS unidade,
						GROUP_CONCAT(w.cantidad_unidad SEPARATOR '*') AS cantidad2
					FROM (
						SELECT q.*,u.*
						FROM inv_asignaciones q
						LEFT JOIN inv_unidades u ON q.unidad_id = u.id_unidad
						ORDER BY u.unidad DESC
					) w
					GROUP BY w.producto_id
				) z ON p.id_producto = z.producto_id 
				WHERE p.visible='s'
				ORDER BY nombre_factura, e.vencimiento ASC")->fetch();

// Define el limite de filas descuento_porcentaje
//var_dump($clientes[0]);
//die();
$limite_longitud = 200;

// Define el limite monetario
$limite_monetario = 10000000;
$limite_monetario = number_format($limite_monetario, 2, '.', '');

// Obtiene los permisos
$permisos = explode(',', permits);

// Almacena los permisos en variables
$permiso_mostrar = in_array('mostrar', $permisos);

$id_user = $_SESSION[user]['id_user'];
$id_rol = $db->query("SELECT rol_id FROM sys_users WHERE id_user='{$id_user}'")->fetch_first()['rol_id'];


// Obtiene el numero de nota
$nro_factura = $db->query(" select MAX(nro_nota) + 1 as nro_factura 
                            from inv_egresos 
                         ")->fetch_first();
                        //    where tipo = 'Venta' and provisionado = 'S'
                            
$nro_factura = $nro_factura['nro_factura'];

// Obtiene empleados
$empleados = $db->query("SELECT CONCAT(e.nombres, ' ', e.paterno, ' ', e.materno) as empleado, e.id_empleado, r.rol, u.id_user, nombre_grupo as codigo
						FROM sys_users u
						INNER JOIN sys_empleados e ON u.persona_id = e.id_empleado
						INNER JOIN sys_roles r ON u.rol_id = r.id_rol
						INNER join inv_clientes_grupos on VENDEDOR_ID=e.id_empleado
						
						LEFT JOIN sys_supervisor ss ON id_cliente_grupo=ss.cliente_grupo_id
                         
						WHERE r.id_rol = 15
						    
						    AND (
            			        (e.id_empleado='".$_user['persona_id']."' AND '".$_user['rol_id']."' = 15)
            			        OR 
            			        (ss.user_ids='".$_user['id_user']."' AND '".$_user['rol_id']."' = 14)
            			        OR 
            			        '".$_user['rol_id']."' = 1
            			    )
            			    
						AND u.active = 1")->fetch();

//if($_user['rol'] == 'Superusuario' || $_user['rol'] == 'Administrador') { 
									
//WHERE r.id_rol != 4 diferente de repartidor

//if($egresoX['plan_de_pagos']=='si'){
    
    $nro_cuotas_X= $db->query(" SELECT COUNT(id_pago_detalle)as nro_cuotas
        						FROM inv_pagos AS p
        						LEFT JOIN inv_pagos_detalles d ON id_pago=pago_id
        						WHERE movimiento_id='".$egresoX['id_egreso']."' AND tipo='Egreso' 
        					 ")->fetch_first();
            
    if($nro_cuotas_X['nro_cuotas']==1 || $nro_cuotas_X['nro_cuotas']=='1'){
        $nro_cuotas_X_contado_query = " SELECT COUNT(id_pago_detalle)as nro_cuotas
                						FROM inv_pagos AS p
                						LEFT JOIN inv_pagos_detalles d ON id_pago=pago_id
                						WHERE fecha='".$egresoX['fecha_egreso']."' AND movimiento_id='".$egresoX['id_egreso']."' AND tipo='Egreso' 
                					 ";
                					 
        $nro_cuotas_X_contado = $db->query($nro_cuotas_X_contado_query)->fetch_first();
    }
            
    $cuotasX= $db->query("  SELECT d.*, p.*
    						FROM inv_pagos AS p
    						LEFT JOIN inv_pagos_detalles d ON id_pago=pago_id
    						WHERE movimiento_id='".$egresoX['id_egreso']."' AND tipo='Egreso' 
    						ORDER BY d.estado DESC, id_pago_detalle ASC 
    					 ")->fetch();

    $nrocuotasX= $db->query("SELECT count(id_pago_detalle)as nro_pagos, SUM(IFNULL(d.monto,0))as monto_pagado
    						 FROM inv_pagos AS p
    						 LEFT JOIN inv_pagos_detalles d ON id_pago=pago_id
    						 WHERE movimiento_id='".$egresoX['id_egreso']."' AND tipo='Egreso' AND d.estado=1  
    					 ")->fetch_first();
    					 
    if($nrocuotasX['monto_pagado'] == NULL){
       $nrocuotasX['monto_pagado'] =0; 
    }
//}
?>
<?php require_once show_template('header-empty'); ?>
<style>
	.position-left-bottom {
		bottom: 0;
		left: 0;
		position: fixed;
		z-index: 1030;
	}

	.margin-all {
		margin: 15px;
	}

	.display-table {
		display: table;
	}

	.display-cell {
		display: table-cell;
		text-align: center;
		vertical-align: middle;
	}

	.btn-circle {
		border-radius: 50%;
		height: 75px;
		width: 75px;
	}

	.width-none {
		width: 10px;
	}

	.table-display>.thead>.tr,
	.table-display>.tbody>.tr,
	.table-display>.tfoot>.tr {
		margin-bottom: 15px;
	}

	.table-display>.thead>.tr>.th,
	.table-display>.tbody>.tr>.th,
	.table-display>.tfoot>.tr>.th {
		font-weight: bold;
	}

	@media (min-width: 768px) {
		.table-display {
			display: table;
		}

		.table-display>.thead,
		.table-display>.tbody,
		.table-display>.tfoot {
			display: table-row-group;
		}

		.table-display>.thead>.tr,
		.table-display>.tbody>.tr,
		.table-display>.tfoot>.tr {
			display: table-row;
		}

		.table-display>.thead>.tr>.th,
		.table-display>.thead>.tr>.td,
		.table-display>.tbody>.tr>.th,
		.table-display>.tbody>.tr>.td,
		.table-display>.tfoot>.tr>.th,
		.table-display>.tfoot>.tr>.td {
			display: table-cell;
		}

		.table-display>.tbody>.tr>.td,
		.table-display>.tbody>.tr>.th,
		.table-display>.tfoot>.tr>.td,
		.table-display>.tfoot>.tr>.th,
		.table-display>.thead>.tr>.td,
		.table-display>.thead>.tr>.th {
			padding-bottom: 15px;
			vertical-align: top;
		}

		.table-display>.tbody>.tr>.td:first-child,
		.table-display>.tbody>.tr>.th:first-child,
		.table-display>.tfoot>.tr>.td:first-child,
		.table-display>.tfoot>.tr>.th:first-child,
		.table-display>.thead>.tr>.td:first-child,
		.table-display>.thead>.tr>.th:first-child {
			padding-right: 15px;
		}
	}

	#cuentasporpagar td {
		padding: 0;
		height: 0;
		border-width: 0px;
	}

	.cuota_div {
		height: 0;
		overflow: hidden;
	}
</style>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-success">
			<div class="panel-heading">
				<h3 class="panel-title">
					<span class="glyphicon glyphicon-option-vertical"></span>
					<strong>Notas de venta</strong>
				</h3>
			</div>
			<div class="panel-body">
				<div class="col-sm-8 hidden-xs">

				</div>
				<div class="col-sm-4 hidden-xs  text-right">
					<div class="form-check form-check-inline">
						<label class="form-check-label" for="inlineCheckbox1">Busqueda de Productos</label>
						<input class="form-check-input" type="checkbox" id="inlineCheckbox1" onchange='sidenav()' checked>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="row" id='ContenedorF'>
	<?php if ($almacen) { ?>
		<div class="col-md-6">
			<div class="panel panel-warning">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-option-vertical"></span>
						<strong>Nota de venta</strong>
					</h3>
				</div>
				<div class="panel-body">
					<h2 class="lead text-warning">Nota de venta : <?= escape($almacen['almacen']); ?></h2>
					<hr>
					<form id="formulario" class="form-horizontal">
					    
					    <input type="hidden" value="<?= $id_egreso_ext ?>" name="id_egreso">
					    
						<div class="form-group">
							<label for="cliente" class="col-sm-4 control-label">Buscar:</label>
							<div class="col-sm-8">
								<select name="cliente" id="cliente" class="form-control text-uppercase" data-validation="letternumber" data-validation-allowing="-+./&() " data-validation-optional="true">
									<option value="">Buscar</option>
									<?php
									foreach ($clientes as $cliente) {
										$Descuento = $cliente['descuento_grupo'];
										if ($cliente['descuento_grupo'] == null) :
											$Descuento = 0;
										endif;
										$Credito = $cliente['credito_grupo'];
										if ($cliente['credito_grupo'] == null) :
											$Credito = 'no';
										endif;
									    ?>
										<option value="<?= escape($cliente['nit']) . '|' . escape($cliente['cliente']) . '|' . escape($cliente['direccion']) . '|' . escape($cliente['ubicacion']) . '|' . escape($cliente['telefono']) . '|' . escape($cliente['id_cliente']) . '|' . $Descuento . '|' . $Credito . '|' . escape($cliente['vendedor_id']) . '|' . escape($cliente['ciudad']) ?>"><?= escape($cliente['cliente']) . ' &mdash; ' . escape($cliente['ciudad']) . ' &mdash; ' . escape($cliente['direccion']); ?></option>
									<?php } ?>
								</select>
								<span class="text-info">Para registrar un nuevo cliente, click en <a href="?/clientes/crear" class="text-success" target="_blank"><b>Nuevo cliente</b></a></span>
								<input type="hidden" name="id_cliente" id="id_cliente" value="<?= $egresoX['cliente_id'] ?>" />
							</div>
						</div>

						
						<div class="form-group">
							<label for="almacen" class="col-sm-4 control-label">Se&ntilde;or(es):</label>
							<div class="col-sm-8 right">
								<div class="input-group">
									<input type="text" value="<?= $egresoX['nombre_cliente'] ?>" name="nombre_cliente" id="nombre_cliente" class="form-control text-uppercase" autocomplete="off" data-validation="required"><!--data-validation="required letternumber length" data-validation-allowing="-+./&() " data-validation-length="max100"-->
									<span class="input-group-addon" style="padding-top: 0px; padding-bottom: 0px;">
										<button type="button" id="ver_datos" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#modalResumenVentas" onclick="mostrar_datos(getElementById('id_cliente').value);">
											Historial
										</button>
									</span>
								</div>
							</div>
						</div>
						
						<input type="hidden" value="<?= $egresoX['nit_ci'] ?>" name="nit_ci" id="nit_ci" class="form-control text-uppercase" autocomplete="off" >
						
						<div class="form-group">
							<label for="ciudad" class="col-sm-4 control-label">Ciudad:</label>
							<div class="col-sm-8">
								<input type="text" value="<?= $clienteX['ciudad'] ?>" name="ciudad" id="ciudad" class="form-control text-uppercase" autocomplete="off">
							</div>
						</div>
						<div class="form-group">
							<label for="direccion" class="col-sm-4 control-label">Direccion:</label>
							<div class="col-sm-8">
								<input type="text" value="<?= $clienteX['direccion'] ?>" name="direccion" id="direccion" class="form-control text-uppercase" autocomplete="off" > <!-- data-validation="required " -->
							</div>
						</div>
						<div class="form-group">
							<label for="empleado" class="col-sm-4 control-label">Asignar vendedor:</label>
							<div class="col-sm-8">
								<select name="empleado" id="empleado" data-empleado class="form-control text-uppercase" data-validation="required" data-validation-allowing="-+./&()">
									<option value="">Buscar....</option>
									<?php foreach ($empleados as $empleado) { ?>
										<option <?php if($egresoX['vendedor_id']==$empleado['id_empleado']){ echo " selected='selected' "; } ?> value="<?= escape($empleado['id_empleado']); ?>" <?= ($empleado['id_user'] == $_user['id_user']) ? 'selected="selected"' : ''?>  ><?= escape($empleado['codigo'] .' - '.$empleado['empleado']); ?></option>
									<?php } ?>
								</select>
							</div>
						</div>

							<div class="form-group" id='CreditoF'>
								<label for="almacen" class="col-sm-4 control-label">Forma de Pago:</label>
								<div class="col-sm-8">
									<select name="forma_pago" id="forma_pago" class="form-control" data-validation="required number" onchange="set_plan_pagos();">
										<option <?php if($egresoX['plan_de_pagos']=='no' && $nro_cuotas_X_contado['nro_cuotas']==1){ echo " selected='selected' "; } ?> value="1">Contado</option>
										<option <?php if($egresoX['plan_de_pagos']=='si' && $nro_cuotas_X_contado['nro_cuotas']!=1){ echo " selected='selected' "; } ?> value="2">Plan de Pagos</option>
									</select>
								</div>
							</div>
						
						<input type="number" class="hidden" id="nro_factura" value="<?= $nro_factura ?>">
						<div class="form-group" id="para_pagos">
							<label for="almacen" class="col-sm-4 control-label">Tipo de Pago:</label>
							<div class="col-sm-8">
								<div class="row">
									<div class="col-md-5">
                                        <select name="tipo_pago" id="tipo_pago" class="form-control" onchange="Ftipo_pago()">
                                            <option <?php if($egresoX['tipo_pago']=='EFECTIVO'){ echo " selected='selected' "; } ?> value="EFECTIVO">Efectivo</option>
                                            <option <?php if($egresoX['tipo_pago']=='CHEQUE'){ echo " selected='selected' "; } ?> value="CHEQUE">Cheque</option>
                                            <option <?php if($egresoX['tipo_pago']=='TRANSFERENCIA'){ echo " selected='selected' "; } ?> value="TRANSFERENCIA">Transferencia</option>
                                            <option <?php if($egresoX['tipo_pago']=='TARJETA'){ echo " selected='selected' "; } ?> value="TARJETA">Tarjeta</option>
                                        </select>
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" name="nro_pago" id="nro_pago" value="<?= $nro_factura ?>" placeholder="Nro documento" class="form-control" autocomplete="off" data-validation="number" aria-label="..." data-validation-optional="true">
                                    </div>
								</div>
							</div>
						</div>

                        <div class="form-group">
							<label for="almacen" class="col-sm-4 control-label">Tipo de Entrega:</label>
							<div class="col-sm-8">
								<div class="row">
									<div class="col-md-12">
                                        <select name="distribuir" id="distribuir" class="form-control" 
                                            <?php if(!($egresoX['estadoe']==2 && $sw_asignacion_clienteX)){ ?> disabled="disabled" <?php } ?> >
                                            <option <?php if($egresoX['distribuir']=='N'){ echo " selected='selected' "; } ?> value="N">Entrega Inmediata</option>
                                            <option <?php if($egresoX['distribuir']=='S'){ echo " selected='selected' "; } ?> value="S">Distribucion</option>
                                        </select>
                                    </div>
								</div>
							</div>
						</div>

                        <div class="form-group">
							<label for="observacion" class="col-sm-4 control-label">Observacion:</label>
							<div class="col-sm-8">
								<textarea name="observacion" id="observacion" class="form-control text-uppercase" rows="2" autocomplete="off" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"><?= $egresoX['descripcion_venta'] ?></textarea>
							</div>
						</div>
						
						<!-- Button trigger modal -->


						<div class="table-responsive margin-none">
							<table id="ventas" class="table table-bordered table-condensed table-striped table-hover table-xs text-sm ">
								<thead>
									<tr class="active">
										<th class="text-nowrap text-center">#</th>
										<th class="text-nowrap text-center">C&Oacute;DIGO</th>
										<th class="text-nowrap text-center" style="width: 20%;">PRODUCTO</th>
										<th class="text-nowrap text-center">CANTIDAD</th>
										<th class="text-nowrap text-center" style="width: 15%;">UNIDAD</th>
										<th class="text-nowrap text-center">PRECIO</th>
										<th class="text-nowrap text-center">IMPORTE</th>
										<th class="text-nowrap text-center">ACCIONES</th>
									</tr>
								</thead>
								<tfoot>
									<tr class="active">
										<th class="text-nowrap text-right" colspan="6">IMPORTE TOTAL <?= escape($moneda); ?></th>
										<th class="text-nowrap text-right" data-subtotal="">0.00</th>
										<th class="text-nowrap text-center">ACCIONES</th>
									</tr>
								</tfoot>
								<tbody>
								    
								    <?php
								    $nroX=0;
								    foreach ($detallesX as $detalleX) {
								        $nroX++;
								        
								        $id_productox=$detalleX['producto_id']."_".$nroX;
								        
								        $lote_cantidad_query = " SELECT SUM(lote_cantidad)as lote_cantidad
                                        						 FROM inv_ingresos_detalles AS d
                                        						 LEFT JOIN inv_ingresos i ON id_ingreso=ingreso_id
                                        						 WHERE  producto_id='".$detalleX['producto_id']."' AND 
                                        						        lote='".$detalleX['lote']."' AND 
                                        						        vencimiento='".$detalleX['vencimiento']."' AND 
                                        						        i.almacen_id='".$egresoX['almacen_id']."'
                                        						";
                                        
                                        $lote_cantidad = $db->query($lote_cantidad_query)->fetch_first();
                                            						
									?>
									
								    <tr class="active" data-producto="<?= $id_productox ?>">
                            			<td class="text-nowrap text-middle"><b>1</b></td>
                            			<td class="text-nowrap text-middle">
                            			    <input type="text" value="<?= $detalleX['producto_id'] ?>" name="productos[]" class="translate input-xs" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser n??mero">
                            			    <?= $detalleX['codigo']." - ".$detalleX['precio'] ?>
                            			</td>
                            			<td class="text-middle"><?= $detalleX['nombre_factura'] ?><br><?= $detalleX['lote'] ?><br><?= $detalleX['vencimiento'] ?>
                                    		<input type="hidden" value='<?= $detalleX['nombre_factura'] ?>' name="nombres[]" class="form-control input-xs" data-validation="required">
                                    		<input type="hidden" value='<?= $detalleX['lote'] ?>' name="lote[]" class="form-control input-xs" data-validation="required">
                                    		<input type="hidden" value='<?= $detalleX['vencimiento'] ?>' name="vencimiento[]" class="form-control input-xs" data-validation="required">
                                		</td>
			                            <td class="text-middle"><input type="text" value="<?= $detalleX['cantidad'] ?>" name="cantidades[]"  class="form-control text-right input-xs" maxlength="10" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-allowing="range[1;<?= ($lote_cantidad['lote_cantidad']+$detalleX['cantidad']) ?>]" data-validation-error-msg="Debe ser un numero positivo entre 1 y <?= ($lote_cantidad['lote_cantidad']+$detalleX['cantidad']) ?>" onkeyup="calcular_importe('<?= $id_productox ?>')"></td>

                                        <td class="text-middle">
				                            <input type="text" value="<?= $detalleX['unidad'] ?>" data-xyyz="<?= $lote_cantidad['lote_cantidad'] ?>" name="unidad[]" class="form-control input-xs text-right" autocomplete="off" data-unidad="' + parte[0] + '" readonly data-validation-error-msg="Debe ser un n??mero decimal positivo">
				                        </td>
				                        <td class="text-middle" data-xyyz="<?= $lote_cantidad['lote_cantidad'] ?>" >
                                            <?php
                                            if($_user['rol_id'] != '1') {
                                                if($producto['id_promocion_precio']!=0){
                            	                ?>			
                                    				<input type="text" readonly value="<?= $detalleX['precio'] ?>" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="<?= $detalleX['precio'] ?>" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo">
                                    				<input type="text" readonly value="<?= $detalleX['precio'] ?>" name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off"  data-pre="<?= $detalleX['precio'] ?>" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo">
                                    			?>
                                    			}else{
                                    			?>
                                    				<input type="text" value="<?= $detalleX['precio'] ?>" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="<?= $detalleX['precio'] ?>" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeypress="SetEnter(event);" onkeyup="modificar_precio('<?= $id_productox ?>'); calcular_importe('<?= $id_productox ?>')">
                                    			    <input type="text" value="<?= $detalleX['precio'] ?>" name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off"  data-pre="<?= $detalleX['precio'] ?>" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeyup="modificar_precio('<?= $id_productox ?>'); calcular_importe('<?= $id_productox ?>')">
                            				    <?php
                                                }
                                            }else{
                                            ?>			
                                				<input type="text" value="<?= $detalleX['precio'] ?>" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="<?= $detalleX['precio'] ?>" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeypress="SetEnter(event);" onkeyup="modificar_precio('<?= $id_productox ?>'); calcular_importe('<?= $id_productox ?>')">
                                				<input type="text" value="<?= $detalleX['precio'] ?>" name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off"  data-pre="<?= $detalleX['precio'] ?>" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeyup="modificar_precio('<?= $id_productox ?>'); calcular_importe('<?= $id_productox ?>')">
                            				<?php
                                            }
                            	            ?>
    				                        <input type="hidden" value="S" readonly name="edit[]" class="form-control input-xs text-right" autocomplete="off" data-precioedit="">
    				                        <input type="hidden" value="" readonly name="asignaciones[]" class="form-control input-xs text-right" autocomplete="off" data-asignacion="" data-validation-error-msg="Debe ser un n??mero decimal positivo">
				                        </td>
                            			<td class="text-nowrap text-middle text-right" data-importe="">0.00</td>
                            			<td class="text-nowrap text-middle text-center">
                            			    <button type="button" class="btn btn-success" tabindex="-1" onclick="eliminar_producto('<?= $id_productox ?>')"><span class="glyphicon glyphicon-trash"></span></button>
                            			</td>
                            		</tr>
                            		
                            		<?php
								    }
									?>
									
								</tbody>
							</table>
						</div>
						<div class="form-group">
							<div class="col-xs-12">
								<input type="text" name="almacen_id" value="<?= $almacen['id_almacen']; ?>" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="El almac??n no esta definido">
								<input type="text" name="nro_registros" value="0" class="translate" tabindex="-1" data-ventas="" data-validation="required number" data-validation-allowing="range[1;<?= $limite_longitud; ?>]" data-validation-error-msg="El n??mero de productos a vender debe ser mayor a cero y menor a <?= $limite_longitud; ?>">
								<input type="text" name="monto_total" value="0" class="translate" tabindex="-1" data-total="" data-validation="required number" data-validation-allowing="range[0.01;<?= $limite_monetario; ?>],float" data-validation-error-msg="El monto total de la venta debe ser mayor a cero y menor a <?= $limite_monetario; ?>">
							</div>
						</div>
						<div class="form-group">
							<div class="col-xs-12">


								<!--Descuentos-->
								<div class="col-xs-12 hidden" id='estado_descuentoF'>
									<div class="col-lg-3  col-md-3 col-xs-3">
										<label for="tipo" class="col-sm-6 control-label">Descuento:</label>
										<div class="col-sm-6">
											<select name="tipo" id="tipo" onchange="tipo_descuento()" class="calcular_descuento form-control" style='width:80px'>
												<option value="0">Bs</option>
												<option value="1">%</option>
											</select>
										</div>
									</div>
									<div class="col-lg-8 col-md-8 col-xs-8"></div>

									<div class="col-xs-3" id="div-descuento" style="display:none">
										<label for="descuento" class="col-sm-4 control-label">(%):</label>
										<div class="col-sm-8">
											<select name="descuento_porc" id="descuento_porc" onchange="calcular_descuento_total()" class="calcular_descuento form-control" style='width:80px' data-validation-length="max100">
												<option value="0">0</option>
											</select>
										</div>
									</div>
									<div class="col-xs-3" id="div-descuento2">
										<label for="descuento" class="col-sm-4 control-label">(Bs):</label>
										<div class="col-sm-8">
											<input type="text" value="0" name="descuento_bs" id="descuento_bs" onkeyup="calcular_descuento_total()" class="calcular_descuento form-control" data-validation="number" data-validation-allowing="range[0.00;<?= $limite_monetario; ?>],float">
										</div>
									</div>

									<div class="col-xs-3">
										<label for="importe_total_descuento" class="col-sm-4 control-label">Importe:</label>
										<div class="col-sm-8">
											<label id="importe_total_descuento" class="calcular_descuento col-sm-6 control-label"></label>
										</div>
										<input type="hidden" name="total_importe_descuento" id="total_importe_descuento">
									</div>
								</div>
								<p>&nbsp;</p>
								

								<div id="plan_de_pagos" style="display: none;">
									<div class="form-group">
										<label for="almacen" class="col-md-4 control-label">Nro Cuotas:</label>
										<div class="col-md-8">
											<input type="text" value="<?php if(isset($nro_cuotas_X)){ echo $nro_cuotas_X['nro_cuotas']; }else{ echo "1"; } ?>" id="nro_cuentas" name="nro_cuentas" class="form-control text-right" autocomplete="off" data-cuentas="" data-validation="required number" data-validation-allowing="range[1;360],int" data-validation-error-msg="Debe ser n??mero entero positivo" onkeyup="set_cuotas()">
										</div>
									</div>

									<table id="cuentasporpagar" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
										<thead>
											<tr class="active">
												<th class="text-nowrap text-center col-xs-3">Detalle</th>
												<th class="text-nowrap text-center col-xs-3">Fecha de Pago</th>
												<th class="text-nowrap text-center col-xs-3">Monto</th>
												<th class="text-nowrap text-center col-xs-3">Estado</th>
											</tr>
										</thead>
										<tbody>

											<?php 
											$nrocuotaX=0;
											$importeTotal=0;
											
									    	if($cuotasX){ 
												foreach ($cuotasX as $cuotaX) {
									    		    $nrocuotaX++;
											        ?>
    												<tr class="active cuotaclass">
														<td class="text-nowrap" valign="center" style="height: auto; border-width: 1px; padding: 5px;">
															<div data-cuota="<?= $nrocuotaX ?>" data-cuota2="<?= $nrocuotaX ?>" class="cuota_div" style="height: auto; overflow: visible;">Cuota <?= $nrocuotaX ?>:</div>
														</td>
													
    													<td style="height: auto; border-width: 1px; padding: 5px;">
    														<div data-cuota="<?= $nrocuotaX ?>" class="cuota_div" style="height: auto; overflow: visible;">
    															<div class="col-sm-12">
    																<input type="hidden" id="estado_pago" name="estado_pago[]" value="<?= $cuotaX['estado'] ?>">
    																<input <?php if($cuotaX['estado']==1){ echo "readonly"; } ?> id="inicial_fecha_<?= $nrocuotaX ?>" name="fecha[]" value="<?php 
    																    $vext=explode("-",$cuotaX['fecha']); 
    																    echo $vext[2]."-".$vext[1]."-".$vext[0]; 
    																?>" min="<?= date('d-m-Y') ?>" class="form-control input-sm" autocomplete="off" <?php if ($nrocuotaX == 1) { ?> data-validation="required date" <?php } ?> data-validation-format="DD-MM-YYYY" data-validation-min-date="<?= date('d-m-Y'); ?>" onchange="javascript:change_date(<?= $nrocuotaX ?>);" onblur="javascript:change_date(<?= $nrocuotaX ?>);">
    															</div>
    														</div>
    													</td>
    													<td style="height: auto; border-width: 1px; padding: 5px;">
    														<div data-cuota="<?= $nrocuotaX ?>" class="cuota_div" style="height: auto; overflow: visible;">
    														    <input type="text" <?php if($cuotaX['estado']==1){ echo "readonly"; } ?> value="<?= $cuotaX['monto'] ?>" name="cuota[]" class="form-control input-sm text-right monto_cuota" maxlength="7" autocomplete="off" data-montocuota="0" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="Debe ser n??mero decimal positivo" onchange="javascript:calcular_cuota('<?= $nrocuotaX ?>');">
    														</div>
    													</td>
    													<td style="height: auto; border-width: 1px; padding: 5px;">
    														<div data-cuota="<?= $nrocuotaX ?>" class="cuota_div" style="height: auto; overflow: visible;">
    														    <?php 
    														    if($cuotaX['estado']==1){ 
    														        echo "Monto pagado";
    														    }else{
    														        echo "Cuota pendiente";
    														    }
    														    ?>
    														</div>
    													</td>
    												</tr>
											        <?php 
											        $importeTotal+=$cuotaX['monto'];
											    }
											} 
											?>
											
											<?php for ($i = ($nrocuotaX+1); $i <= 36; $i++) { ?>
												<tr class="active cuotaclass">
													<?php if ($i == 1) { ?>
														<td class="text-nowrap" valign="center">
															<div data-cuota="<?= $i ?>" data-cuota2="<?= $i ?>" class="cuota_div">Pago Inicial:</div>
														</td>
													<?php } else { ?>
														<td class="text-nowrap" valign="center">
															<div data-cuota="<?= $i ?>" data-cuota2="<?= $i ?>" class="cuota_div">Cuota <?= $i ?>:</div>
														</td>
													<?php } ?>

													<td>
														<div data-cuota="<?= $i ?>" class="cuota_div">
															<div class="col-sm-12">
																<input type="hidden" id="estado_pago" name="estado_pago[]" value="0">
    															
    															<input  id="inicial_fecha_<?= $i ?>" name="fecha[]" value="<?= date('d-m-Y') ?>" min="<?= date('d-m-Y') ?>" class="form-control input-sm" autocomplete="off" <?php if ($i == 1) { ?> data-validation="required date" <?php } ?> data-validation-format="DD-MM-YYYY" data-validation-min-date="<?= date('d-m-Y'); ?>" onchange="javascript:change_date(<?= $i ?>);" onblur="javascript:change_date(<?= $i ?>);" <?php if ($i > 1) { ?> disabled="disabled" <?php } ?>>
															</div>
														</div>
													</td>
													<td>
														<div data-cuota="<?= $i ?>" class="cuota_div"><input type="text" value="0" name="cuota[]" class="form-control input-sm text-right monto_cuota" maxlength="7" autocomplete="off" data-montocuota="0" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="Debe ser n??mero decimal positivo" onchange="javascript:calcular_cuota('<?= $i ?>');"></div>
													</td>
													<td>
														<div data-cuota="<?= $i ?>" class="cuota_div">
														    Cuota pendiente
														</div>
													</td>
												</tr>
											<?php } ?>
										</tbody>
										<tfoot>
											<tr class="active">
												<th class="text-nowrap text-center" colspan="2">Importe total <?= escape($moneda); ?></th>
												<th class="text-nowrap text-right" data-totalcuota=""><?= $importeTotal ?></th>
												<th class="text-nowrap text-right"></th>
											</tr>
										</tfoot>
									</table>
									<br>
								</div>

                                <div class="col-xs-6 text-right">
									<button type="submit" class="btn btn-warning" onmouseup="SetGuardar(event);">Guardar</button>
									<button type="reset" class="btn btn-default">Restablecer</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
			<div class="panel panel-success hidden" id="para_deudas">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-menu-hamburger"></span>
						<strong>Historial de deudas del cliente</strong>
					</h3>
				</div>
				<div class="panel-body">
					<div id="historial_deudas" class="text-center"></div>
				</div>
			</div>
			<div class="panel panel-warning" data-servidor="<?= ip_local . name_project . '/nota.php'; ?>">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-menu-hamburger"></span>
						<strong>Informaci&oacute;n sobre la transacci&oacute;n</strong>
					</h3>
				</div>
				<div class="panel-body">
					<h2 class="lead text-warning">Informaci&oacute;n sobre la transacci&oacute;n</h2>
					<hr>
					<div class="table-display">
						<div class="tbody">
							<div class="tr">
								<div class="th">
									<span class="glyphicon glyphicon-home"></span>
									<span>Casa matriz:</span>
								</div>
								<div class="td"><?= escape($_institution['nombre']); ?></div>
							</div>
							<div class="tr">
								<div class="th">
									<span class="glyphicon glyphicon-qrcode"></span>
									<span>NIT:</span>
								</div>
								<div class="td"><?= escape($_institution['nit']); ?></div>
							</div>
							<?php if ($_terminal) : ?>
								<div class="tr">
									<div class="th">
										<span class="glyphicon glyphicon-phone"></span>
										<span>Terminal:</span>
									</div>
									<div class="td"><?= escape($_terminal['terminal']); ?></div>
								</div>
								<div class="tr">
									<div class="th">
										<span class="glyphicon glyphicon-print"></span>
										<span>Impresora:</span>
									</div>
									<div class="td"><?= escape($_terminal['impresora']); ?></div>
								</div>
							<?php endif ?>
							<div class="tr">
								<div class="th">
									<span class="glyphicon glyphicon-user"></span>
									<span>Empleado:</span>
								</div>
								<div class="td"><?= ($_user['persona_id'] == 0) ? 'No asignado' : escape($_user['nombres'] . ' ' . $_user['paterno'] . ' ' . $_user['materno']); ?></div>
							</div>
						</div>
					</div>
				</div>
				<div class="panel-footer text-center"><?= credits; ?></div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-search"></span>
						<strong>B&uacute;squeda de productos</strong>
					</h3>
				</div>
				<div class="panel-body">
					<h2 class="lead">B&uacute;squeda de productos</h2>
					<hr>
					<?php if ($permiso_mostrar) : ?>
						<p class="text-right">
							<a href="?/notas/mostrar" class="btn btn-warning">Mis notas de venta</a>
						</p>
					<?php endif ?>

					<?php if ($productos) { ?>
						<table id="productosTable" class="table table-bordered table-condensed table-striped table-hover table-xs text-sm">
							<thead>
								<tr class="active">
									<th class="text-nowrap">Imagen</th>
									<th class="text-nowrap">C&oacute;digo</th>
									<th class="text-nowrap">Producto</th>
									<th class="text-nowrap">Stock</th>
									<th class="text-nowrap">Precio</th>
									<th class="text-nowrap">Acciones</th>
									<th class="text-nowrap hidden"></th>
									<th class="text-nowrap hidden"></th>
								</tr>
							</thead>
							<tbody>
								<?php 
								$product_color=0;
								foreach ($productos as $nro => $producto) { 
									$otro_precio=$db->query("SELECT *
									FROM inv_asignaciones a
									LEFT JOIN inv_unidades b ON a.unidad_id=b.id_unidad
									WHERE a.producto_id='{$producto['id_producto']}'")->fetch();


									// echo json_encode($otro_precio);
									if ((escape($producto['cantidad_ingresos'] - $producto['cantidad_egresos'])) > 0) {
								?>
									<tr data-busqueda="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>">
										<td class="text-nowrap text-middle text-center width-none" <?php 
										    if($product_color==$producto['id_producto']){
    										    echo ' style="background-color:rgb(128,30,30);" ';
										    }else{
									            $product_color=$producto['id_producto'];
									            echo ' style="background-color:rgb(60,128,60);" ';
										    }
										    ?> >
											<img src="<?= ($producto['imagen'] == '') ? imgs . '/image.jpg' : files . '/productos/' . $producto['imagen']; ?>" class="img-rounded cursor-pointer" data-toggle="modal" data-target="#modal_mostrar" data-modal-size="modal-md" data-modal-title="Imagen" width="75" height="75">
										</td>
										<td class="text-nowrap text-middle" data-codigo="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>" data-codigo2=""><?= escape($producto['codigo']); ?></td>
										<td class="text-middle">
											<em><?= escape($producto['nombre']); ?></em>
											<br>
											<span class="vencimientoView" data-vencimientoview=""><?= escape($producto['vencimiento']); ?></span>
											<br>
											<span class="loteView" data-loteview=""><?= escape($producto['lote']); ?></span>
											<span class="editar hidden" data-editar="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?php
											    if($producto['id_promocion_precio']==0){   
											        echo "N";    }
											    else{   
											        echo "S";    }
											?></span>
											<span class="vencimiento hidden" data-vencimiento="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['vencimiento']); ?></span>
											<span class="lote hidden" data-lote="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['lote']); ?></span>
											<span class="nombre_producto hidden" data-nombre="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['nombre']); ?></span>
										</td>
										<td class="text-nowrap text-middle text-right" data-stock="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['cantidad_ingresos'] - $producto['cantidad_egresos']); ?></td>
										<td class="text-middle text-right" data-asignacion="" style="font-weight: bold; font-size: 0.8em;">
										    <?php echo escape($producto['precio_actual']); ?>
    										<div data-valor="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>" style="display:none;">
    											<?php
    												echo '*(1)'.escape($producto['unidad']).': <b>'.escape($producto['precio_actual']).'</b>';
    												foreach ($otro_precio as $otro){
    											?>
    												<br />*(<?= escape($producto['cantidad2']) . ')' .escape($otro['unidad'] . ': '); ?><b><?= escape($otro['otro_precio']); ?></b>
    											<?php } ?>
											</div>
										</td>
										<td class="text-nowrap text-middle text-center width-none">
											<button type="button" class="btn btn-warning" data-vender="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>" onclick="vender(this);" title="Vender">
											<span class="glyphicon glyphicon-shopping-cart"></span>
											<!--Vender-->
											</button>
											<!--<button type="button" class="btn btn-default" data-actualizar="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>" onclick="actualizar(this, <?= $id_almacen ?>);calcular_descuento()" title="Actualizar">-->
											<!--<span class="glyphicon glyphicon-refresh"></span>-->
											<!--Actualizar-->
											<!--</button>-->
										</td>
										<td class="hidden" data-cant="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['cantidad2']); ?></td>
										<td class="hidden" data-stock2="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['cantidad_ingresos'] - $producto['cantidad_egresos']); ?></td>
									</tr>
								<?php }  // fin if
							} // fin foreach ?>
							</tbody>
						</table>
					<?php } else { ?>
						<div class="alert alert-danger">
							<strong>Advertencia!</strong>
							<p>No existen productos registrados en la base de datos.</p>
						</div>
					<?php } ?>


					<div id="contenido_filtrar"></div>
				</div>
			</div>
		</div>
	<?php } else { ?>
		<div class="col-xs-12">
			<div class="panel panel-success">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-option-vertical"></span>
						<strong>Nota de venta</strong>
					</h3>
				</div>
				<div class="panel-body">
					<div class="alert alert-danger">
						<p>Usted no puede realizar notas de venta, verifique que la siguiente informaci&oacute;n sea correcta:</p>
						<ul>
							<li>El almac??n principal no esta definido, ingrese al apartado de "almacenes" y designe a uno de los almacenes como principal.</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
</div>
<h2 class="btn-warning position-left-bottom display-table btn-circle margin-all display-table" data-toggle="tooltip" data-title="Esto es una nota de remisi??n" data-placement="right">
	<span class="glyphicon glyphicon-star display-cell"></span>
</h2>

<!-- Plantillas filtrar inicio -->
<div id="tabla_filtrar" class="hidden">
	<div class="table-responsive">
		<table class="table table-bordered table-condensed table-striped table-hover">
			<thead>
				<tr class="active">
					<th class="text-nowrap text-middle text-center width-none">Imagen</th>
					<th class="text-nowrap text-middle text-center">C&oacute;digo</th>
					<th class="text-nowrap text-middle text-center">Producto</th>
					<th class="text-nowrap text-middle text-center">Categor??a</th>
					<th class="text-nowrap text-middle text-center">Stock</th>
					<th class="text-middle text-center" width="18%">Precio</th>
					<th class="text-nowrap text-middle text-center width-none">Acciones</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</div>

<table class="hidden">
	<tbody id="fila_filtrar" data-negativo="<?= imgs; ?>/" data-positivo="<?= files; ?>/productos/">
		<tr>
			<td class="text-nowrap text-middle text-center width-none">
				<img src="" class="img-rounded cursor-pointer" data-toggle="modal" data-target="#modal_mostrar" data-modal-size="modal-md" data-modal-title="Imagen" width="75" height="75">
			</td>
			<td class="text-nowrap text-middle" data-codigo="" data-codigo2="">				
			</td>
			<td class="text-middle">
				<em></em>
				<br>
				<span class="vencimientoView" data-vencimientoView=""></span>
				<br>
				<span class="loteView" data-loteView=""></span>
				<span class="vencimiento hidden" data-vencimiento=""></span>
				<span class="lote hidden" data-lote=""></span>
				<span class="nombre_producto hidden" data-nombre=""></span>
			</td>
			<td class="text-nowrap text-middle"></td>
			<td class="text-nowrap text-middle text-right" data-stock=""></td>
			<td class="text-middle text-right" data-valor="" data-asignacion=""><div class="stockpromocion hidden" data-stockpromocion=""></div></td>
			<td class="text-nowrap text-middle text-center width-none">
				<button type="button" class="btn btn-warning" data-vender="" onclick="vender(this);">
				<span class="glyphicon glyphicon-shopping-cart"></span>
				<!--Vender-->
				</button>
				<button type="button" class="btn btn-default" data-actualizar="" onclick="actualizar(this, <?= $id_almacen ?>);calcular_descuento()">
				<span class="glyphicon glyphicon-refresh"></span>
				<!--Actualizar-->
				</button>
			</td>
			<td class="hidden" data-cant=""></td>
			<td class="hidden" data-stock2=""></td>
		</tr>
	</tbody>
</table>
<div id="mensaje_filtrar" class="hidden">
	<div class="alert alert-danger">No se encontraron resultados</div>
</div>
<!-- Plantillas filtrar fin -->

<!-- Modal mostrar inicio -->
<div id="modal_mostrar" class="modal fade" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content loader-wrapper">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"></h4>
			</div>
			<div class="modal-body">
				<img src="" class="img-responsive img-rounded" data-modal-image="">
			</div>
			<div id="loader_mostrar" class="loader-wrapper-backdrop">
				<span class="loader"></span>
			</div>
		</div>
	</div>
</div>
<!-- Modal mostrar fin -->
<div id="ot">
	<div class="modal fade" id="cantidadModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" id="close" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label for="recipient-name" class="control-label">Cantidad:</label>
						<input type="number" class="form-control" id="recip" required="required" autofocus>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
					<button type="button" class="btn btn-primary" id="modcant">Enviar</button>
				</div>
			</div>
		</div>
	</div>
</div>
<input type='hidden' id='descuentoGrupoF' value='0'>

<!-- Modal -->
<div class="modal fade" id="modalResumenVentas" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable" role="document" > <!-- style="max-width: 80em !important;" -->
		<div class="modal-content ">
			<div class="modal-header">
				<button type="button" id="close" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Historial de compras del cliente</h4>
			</div>
			<div class="modal-body">
				<div id="compras_cliente" class=" text-center"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
				<!-- <button type="button" class="btn btn-primary">Save</button> -->
			</div>
		</div>
	</div>
</div>


<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/selectize.min.js"></script>
<script src="<?= js; ?>/bootstrap-notify.min.js"></script>
<script src="<?= js; ?>/buzz.min.js"></script>
<script src="<?= js; ?>/bootstrap-datetimepicker.min.js"></script>

<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/number_format.js"></script>
<script>
    var SwGuardar=false;
	var SwActualizarCuotas=false;
	var idp;

    function SetEnter(event){
        if (event.keyCode === 13) {
            document.getElementById("id_buscador").focus();
            
            $("html, body").animate({
                scrollTop: 0
            }, 500);
        }
    }
	function cantidad(el) {
		var $elemento = $(el);
		var id_prod;
		id_prod = $elemento.attr('data-vender');
		idp = id_prod;
		//    console.log(id_prod);
		$("#cantidadModal").modal('show');
		$('#recip').val('');
		//$('#recip').focus();
	}

	$("#modcant").on('click', function() {
		var aa;
		aa = $('#recip').val();
		adicionar_producto(idp, aa);
		$("#cantidadModal").modal('hide'); //ocultamos el modal
		//$('.fade').close();
		$('#ot').removeClass('modal-open');
		$('.modal-backdrop').remove();
	});

	function vender(elemento) {
		var $elemento = $(elemento);
		var	vender;
		vender = $elemento.attr('data-vender');
		adicionar_producto(vender);
	}

	$(function() {
		$('#productosTable').dataTable({
            info: false,
            scrollY: "35em",
            scrollCollapse: true,
            lengthMenu: [
                [25, 50, 100, 500, -1],
                [25, 50, 100, 500, 'Todos']
            ],
            order: []
		});
		
        $("html, body").animate({
            scrollTop: 0
        }, 500);

        $('#nro_pago').css({'display':'none'});
        
        $('#productosTable_filter').children('label').children('.form-control').attr('id','id_buscador');
        
        var $cliente = $('#cliente');
		var $nit_ci = $('#nit_ci');
		var $nombre_cliente = $('#nombre_cliente');

		var $direccion = $('#direccion');
		var $ciudad = $('#ciudad');
		var $telefono = $('#telefono');
		var $ubicacion = $('#ubicacion');

		var $id_cliente = $('#id_cliente');
		var $formulario = $('#formulario');
		let almacen = <?= $id_almacen; ?>;
		var $vendedor_id;

		// Josema::add
		if ($nit_ci.val() == '' && $nombre_cliente.val() == '') {
			$("#ver_datos").attr('disabled', true);
		} else {
			$("#ver_datos").attr('disabled', false);
		}
		// Josema::add


		
		$cliente.selectize({
			persist: false,
			createOnBlur: true,
			create: false,
			onInitialize: function() {
				$cliente.css({
					display: 'block',
					left: '-10000px',
					opacity: '0',
					position: 'absolute',
					top: '-10000px'
				});
			},
			onChange: function() {
				$cliente.trigger('blur');
			},
			onBlur: function() {
				$cliente.trigger('blur');
			}
		}).on('change', function(e) {
			var valor = $(this).val();
			valor = valor.split('|');
			$(this)[0].selectize.clear();
			$('#tipo').val(0);
			if (valor.length != 1) {
				$nit_ci.prop('readonly', true);
				$nombre_cliente.prop('readonly', true);
				$telefono.prop('readonly', true);
				$direccion.prop('readonly', true);
				$ciudad.prop('readonly', true);
				$nit_ci.val(valor[0]);
				$id_cliente.val(valor[5]);
				$nombre_cliente.val(valor[1]);
				$telefono.val(valor[4]);
				$direccion.val(valor[2]);
				descuento_pago_pristine([valor[6], (valor[7] === 'si') ? 1 : 0]);
				$vendedor_id = valor[8];
				$ciudad.val(valor[9]);
				seleccionar_vendedor($vendedor_id);

				// Josema::add
				if ($nit_ci.val == '' && $nombre_cliente == '') {
					$("#ver_datos").attr('disabled', true);
				} else {
					$("#ver_datos").attr('disabled', false);
				}
				// Josema::add
			} else {
				if (es_nit(valor[0])) {
					$nit_ci.val(valor[0]);
					$nombre_cliente.val('').focus();
				} else {
					$nombre_cliente.val(valor[0]);
					$nit_ci.val('').focus();
				}
				descuento_pago_pristine();
			}
			if($('#nombre_cliente').val() != ''){
				mostrar_datos($('#id_cliente').val());
			}
		});
		$('#descuento_porc').selectize({
			persist: false,
			createOnBlur: true,
			create: false,
			onInitialize: function() {
				$('#descuento_porc').css({
					display: 'block',
					left: '-10000px',
					opacity: '0',
					position: 'absolute',
					top: '-10000px'
				});
			},
			onChange: function() {
				$('#descuento_porc').trigger('blur');
			},
			onBlur: function() {
				$('#descuento_porc').trigger('blur');
			}
		}).on('change', function(e) {
			let valor = $(this).val();
			valor = valor.trim();
			if (valor != '')
				calcular_descuento_total();
		});

		$.validate({
			form: '#formulario',
			modules: 'basic',
			onSuccess: function() {
				guardar_nota();
			}
		});

		$formulario.on('submit', function(e) {
			e.preventDefault();
		});

		$formulario.on('reset', function() {
			$nit_ci.prop('readonly', true);
			$nombre_cliente.prop('readonly', true);
			$direccion.prop('readonly', true);
			$ciudad.prop('readonly', true);
			$telefono.prop('readonly', true);
			$ubicacion.prop('readonly', true);
			calcular_total();
		}).trigger('reset');

		var blup = new buzz.sound('<?= media; ?>/blup.mp3');

		var $form_filtrar = $('#form_buscar_0, #form_buscar_1'),
			$contenido_filtrar = $('#contenido_filtrar'),
			$tabla_filtrar = $('#tabla_filtrar'),
			$fila_filtrar = $('#fila_filtrar'),
			$mensaje_filtrar = $('#mensaje_filtrar'),
			$modal_mostrar = $('#modal_mostrar'),
			$loader_mostrar = $('#loader_mostrar');

		$form_filtrar.on('submit', function(e) {
			e.preventDefault();
			var $this, url, busqueda;
			$this = $(this);
			url = $this.attr('action');
			busqueda = $this.find(':text').val();
			$this.find(':text').attr('value', '');
			$this.find(':text').val('');
			if ($.trim(busqueda) != '') {
				$.ajax({
					type: 'post',
					dataType: 'json',
					url: url,
					data: {
						busqueda: busqueda,
						almacen: <?= $id_almacen ?>
					}
				}).done(function(productos) {

					console.log(productos);
					if (productos.length) {

						var $ultimo;
						var $ultimo2;
						$contenido_filtrar.html($tabla_filtrar.html());
						for (var i in productos) {
                            console.log(productos[i].id_producto+'_'+productos[i].id_detalle);
							if ((parseInt(productos[i].cantidad_ingresos) - parseInt(productos[i].cantidad_egresos)) > 0) {
								productos[i].imagen = (productos[i].imagen == '') ? $fila_filtrar.attr('data-negativo') + 'image.jpg' : $fila_filtrar.attr('data-positivo') + productos[i].imagen;
								// productos[i].codigo = productos[i].codigo;
								$contenido_filtrar.find('tbody').append($fila_filtrar.html());
								$contenido_filtrar.find('tbody tr:last').attr('data-busqueda', productos[i].id_producto+'_'+productos[i].id_detalle);

								if (productos[i].promocion === 'si') {
									$ultimo = $contenido_filtrar.find('tbody tr:nth-last-child(1)').children().addClass('warning');
								} else {
									$ultimo = $contenido_filtrar.find('tbody tr:nth-last-child(1)').children();
								}
								$ultimo2 = $contenido_filtrar.find('tbody tr:last').children();
								$ultimo2.eq(0).find('em2').text(productos[i].descripcion);
								$ultimo.eq(0).find('img').attr('src', productos[i].imagen);
								$ultimo.eq(1).attr('data-codigo', productos[i].id_producto);
								$ultimo.eq(1).attr('data-codigo', productos[i].id_producto+'_'+productos[i].id_detalle);
								$ultimo.eq(1).text(productos[i].codigo);
								$ultimo.eq(2).find('em').text(productos[i].nombre);

								$ultimo.eq(2).find('.vencimientoView').text(productos[i].vencimiento);
								$ultimo.eq(2).find('.loteView').text(""+productos[i].lote);

								$ultimo.eq(2).find('.vencimiento').text(""+productos[i].vencimiento);
								$ultimo.eq(2).find('.lote').text(""+productos[i].lote);

								$ultimo.eq(2).find('.nombre_producto').text(productos[i].nombre);

								$ultimo.eq(2).find('.nombre_producto').attr('data-nombre', productos[i].id_producto+'_'+productos[i].id_detalle);
								$ultimo.eq(2).find('.vencimiento').attr('data-vencimiento', productos[i].id_producto+'_'+productos[i].id_detalle);
								$ultimo.eq(2).find('.lote').attr('data-lote', productos[i].id_producto+'_'+productos[i].id_detalle);

                                let str = '';
                                if (productos[i].asignacion_rol !== '') {
                                    let asignacion_rol = productos[i].asignacion_rol.split(',');
                                    if (asignacion_rol.includes('<?= $id_rol ?>'))
                                        str = `*(1)${productos[i].unidad}:${productos[i].precio_actual}`;
                                } else
                                    str = `*(1)${productos[i].unidad}:${productos[i].precio_actual}`;
                                if (typeof(productos[i].id_roles) !== 'object') {
                                    let id_roles = productos[i].id_roles.split(',');
                                    let Arreglo = [];
                                    for (let i = 0; i < id_roles.length; ++i) {
                                        let detalle = id_roles[i].split('|');
                                        let rolA = detalle[0];
                                        let asigA = detalle[1];
                                        if (rolA === '<?= $id_rol ?>')
                                            Arreglo.push(asigA);
                                    }
                                    if (typeof(productos[i].unidade) === 'object')
                                        productos[i].unidade = '';
                                    if (productos[i].unidade !== '') {
                                        let Aux = productos[i].unidade.split('&'),
                                            id_asignacion = productos[i].id_asignacion.split('|');
                                        for (let i = 0; i < Aux.length; ++i) {
                                            if (Arreglo.includes(id_asignacion[i]))
                                                str += ` \n *(${Aux[i]}`;
                                        }
                                    }
                                } else {
                                    if (typeof(productos[i].unidade) === 'object')
                                        productos[i].unidade = '';
                                    if (productos[i].unidade !== '') {
                                        let Aux = productos[i].unidade.split('&');
                                        for (let i = 0; i < Aux.length; ++i)
                                            str += ` \n *(${Aux[i]}`;
                                    }
                                }
                                let res = str;


								$ultimo.eq(4).attr('data-stock', productos[i].id_producto+'_'+productos[i].id_detalle);
								$ultimo.eq(4).text(parseInt(productos[i].cantidad_ingresos) - parseInt(productos[i].cantidad_egresos));


								$ultimo.eq(5).css("font-weight", "bold");
								$ultimo.eq(5).css("font-size", "0.8em");
								$ultimo.eq(5).attr('data-valor', productos[i].id_producto+'_'+productos[i].id_detalle);
								$ultimo.eq(5).text(res);

								$ultimo.eq(6).find(':button:first').attr('data-vender', productos[i].id_producto+'_'+productos[i].id_detalle);
								$ultimo.eq(6).find(':button:last').attr('data-actualizar', productos[i].id_producto+'_'+productos[i].id_detalle);
								$ultimo.eq(7).attr('data-cant', productos[i].id_producto+'_'+productos[i].id_detalle);
								$ultimo.eq(7).text(productos[i].cantidad2);

								$ultimo.eq(8).attr('data-stock2', productos[i].id_producto+'_'+productos[i].id_detalle);
								$ultimo.eq(8).text(parseInt(productos[i].cantidad_ingresos) - parseInt(productos[i].cantidad_egresos));

								$ultimo.eq(9).attr('data-contado', productos[i].id_producto+'_'+productos[i].id_detalle);
								$ultimo.eq(9).text(productos[i].descuento);
							}
						}
						if (productos.length == 1) {
							$contenido_filtrar.find('table tbody tr button').trigger('click');
						}
						$.notify({
							message: 'La operaci??n fue ejecutada con ??xito, se encontraron ' + productos.length + ' resultados.'
						}, {
							type: 'success'
						});
						blup.stop().play();
					} else {
						$contenido_filtrar.html($mensaje_filtrar.html());
					}
				}).fail(function() {
					$contenido_filtrar.html($mensaje_filtrar.html());
					$.notify({
						message: 'La operaci??n fue interrumpida por un fallo.'
					}, {
						type: 'danger'
					});
					blup.stop().play();
				});
			} else {
				$contenido_filtrar.html($mensaje_filtrar.html());
			}
		}).trigger('submit');

		var $modal_mostrar = $('#modal_mostrar'),
			$loader_mostrar = $('#loader_mostrar'),
			size, title, image;

		$modal_mostrar.on('hidden.bs.modal', function() {
			$loader_mostrar.show();
			$modal_mostrar.find('.modal-dialog').attr('class', 'modal-dialog');
			$modal_mostrar.find('.modal-title').text('');
		}).on('show.bs.modal', function(e) {
			size = $(e.relatedTarget).attr('data-modal-size');
			title = $(e.relatedTarget).attr('data-modal-title');
			image = $(e.relatedTarget).attr('src');
			size = (size) ? 'modal-dialog ' + size : 'modal-dialog';
			title = (title) ? title : 'Imagen';
			$modal_mostrar.find('.modal-dialog').attr('class', size);
			$modal_mostrar.find('.modal-title').text(title);
			$modal_mostrar.find('[data-modal-image]').attr('src', image);
		}).on('shown.bs.modal', function() {
			$loader_mostrar.hide();
		});

		$('.calcular_descuento').on('keyup blur', function() {
			calcular_descuento();
		})
		
		<?php
		$nroX=0;
		foreach ($detallesX as $detalleX) {
		    $nroX++;
		    $id_productox=$detalleX['producto_id']."_".$nroX;
		    ?>
		    calcular_importe('<?= $id_productox ?>');
		    <?php
		}
        ?>	
        
        if($('#forma_pago').val() == 2) {
            $('#para_pagos').addClass('hidden');
        }
        if ($("#forma_pago").val() == 1) {
			$('#plan_de_pagos').css({
				'display': 'none'
			});
			if ($('#nro_cuentas').val() <= 0) {
				$('#nro_cuentas').val('1');
			}
		} else {
			$('#plan_de_pagos').css({
				'display': 'block'
			});
		}
	
	    setTimeout(function(){ SwActualizarCuotas=true;	},1500);
	    	
	});

	function es_nit(texto) {
		var numeros = '0123456789';
		for (i = 0; i < texto.length; i++) {
			if (numeros.indexOf(texto.charAt(i), 0) != -1) {
				return true;
			}
		}
		return false;
	}

	function adicionar_producto(id_producto) {
		
		var $ventas = $('#ventas tbody');
		var $producto = $ventas.find('[data-producto=' + id_producto + ']');
		//id_producto = id_producto[id_producto.length - 1];
		//console.log(id_producto);
		//console.log('hola');
		var $cantidad = $producto.find('[data-cantidad]');
		var numero = $ventas.find('[data-producto]').size() + 1;
		var codigo = $.trim($('[data-codigo=' + id_producto + ']').text());

		var nombre = $.trim($('[data-nombre=' + id_producto + ']').text());
		var lote = $.trim($('[data-lote=' + id_producto + ']').text());
		var vencimiento = $.trim($('[data-vencimiento=' + id_producto + ']').text());
		var editarxxx = $.trim($('[data-editar=' + id_producto + ']').text());

		var cantidad2 = $.trim($('[data-cant=' + id_producto + ']').text());
		var stock = $.trim($('[data-stock=' + id_producto + ']').text());
		var valor = $.trim($('[data-valor=' + id_producto + ']').text());
		var asignaciones = $.trim($('[data-valor=' + id_producto + ']').attr("data-asignacion"));

		var plantilla = '';
		var cantidad;
		var $modcant = $('#modcant');

		var posicion = valor.indexOf(':');
		var porciones = valor.split('*');

		cantidad2 = '1*' + cantidad2;
		z = 1;
		var porci2 = cantidad2.split('*');
		//console.log(porci2);
		
		V_producto_simple=id_producto.split("_");
		id_producto_simple=V_producto_simple[0];

        var dt = new Date();
        var time = dt.getHours() + "" + dt.getMinutes() + "" + dt.getSeconds() + "" + dt.getMilliseconds();
        id_producto=id_producto+"_"+time;

        plantilla = '<tr class="active" data-producto="' + id_producto + '">' +
			'<td class="text-nowrap text-middle"><b>' + numero + '</b></td>' +
			'<td class="text-nowrap text-middle"><input type="text" value="' + id_producto_simple + '" name="productos[]" class="translate input-xs" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser n??mero">' + codigo + '</td>' +
			'<td class="text-middle">'+nombre+'<br>'+lote+'<br>'+vencimiento+'';

		plantilla += '<input type="hidden" value=\'' + nombre + '\' name="nombres[]" class="form-control input-xs" data-validation="required">';
		plantilla += '<input type="hidden" value=\'' + lote + '\' name="lote[]" class="form-control input-xs" data-validation="required">';
		plantilla += '<input type="hidden" value=\'' + vencimiento + '\' name="vencimiento[]" class="form-control input-xs" data-validation="required">';

		plantilla += '</td>' +
			'<td class="text-middle"><input type="text" value="1" name="cantidades[]"  class="form-control text-right input-xs" maxlength="10" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-allowing="range[1;' + stock + ']" data-validation-error-msg="Debe ser un n??mero positivo entre 1 y ' + stock + '" onkeyup="calcular_importe(\'' + id_producto + '\')"></td>';

		//si tiene mas de una unidad
		if (porciones.length > 2) {
			plantilla = plantilla + '<td class="text-middle"><select name="unidad[]" id="unidad[]" data-unidad="" data-xxx="true" class="form-control input-xs" style="padding-left:0;" >';
			aparte = porciones[1].split(':');
			asignation = asignaciones.split('|');

			// console.log(porciones);

			for (var ic = 1; ic < porciones.length; ic++) {
				parte = porciones[ic].split(':');
				oparte = parte[0].split(')');
				plantilla = plantilla + '<option value="'+oparte[1]+'" data-pr="'+id_producto+'" data-xyyz="'+stock+'" data-yyy="'+parte[1]+'" data-yyz="'+porci2[ic - 1] + '" data-asig="' + asignation[ic - 1] + '" >' + oparte[1] + ' ('+parte[1]+')</option>';
			}
			plantilla = plantilla + '</select></td>' +
				'<td class="text-middle">' +

				'<input type="text" value="' + aparte[1] + '" readonly name="precios[]" class="form-control input-xs text-right class_enter" autocomplete="off" data-precio="' + aparte[1] + '"   data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeyup="calcular_importe(\'' + id_producto + '\')">' +
				'<input type="text" value="' + aparte[1] + '" readonly name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off" data-pre="' + aparte[1] + '"   data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeyup="calcular_importe(\'' + id_producto + '\')">' +
				
				'<input type="hidden" value="' + asignation[0] + '" readonly name="asignaciones[]" class="form-control input-xs text-right" autocomplete="off" data-asignacion="' + asignation[0] + '"   data-validation-error-msg="Debe ser un n??mero decimal positivo">' +
				
				'</td>';
		} else {
			asignation=asignaciones.split('|');
			sincant = porciones[1].split(')');
			//            console.log(sincant);
			parte = sincant[1].split(':');
			plantilla = plantilla + '<td class="text-middle">' +
				'<input type="text" value="' + parte[0] + '" data-xyyz="' + stock + '" name="unidad[]" class="form-control input-xs text-right" autocomplete="off" data-unidad="' + parte[0] + '" readonly data-validation-error-msg="Debe ser un n??mero decimal positivo"></td>' +
				'<td class="text-middle" data-xyyz="' + stock + '" >';

                <?php
                if($_user['rol_id'] != '1') {
	            ?>			
        			if(editarxxx!='S') {
    	            	plantilla = plantilla + '<input type="text" readonly value="' + parte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo">' +
        				                        '<input type="text" readonly value="' + parte[1] + '" name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off"  data-pre="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo">';
        			}else{
        				plantilla = plantilla + '<input type="text" value="' + parte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeypress="SetEnter(event);" onkeyup="modificar_precio(\'' + id_producto + '\'); calcular_importe(\'' + id_producto + '\')">' +
        				                        '<input type="text" value="' + parte[1] + '" name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off"  data-pre="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeyup="modificar_precio(\'' + id_producto + '\'); calcular_importe(\'' + id_producto + '\')">';
        			}
				<?php
                }else{
                ?>			
    				plantilla = plantilla + '<input type="text" value="' + parte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeypress="SetEnter(event);" onkeyup="modificar_precio(\'' + id_producto + '\'); calcular_importe(\'' + id_producto + '\')">' +
    				                        '<input type="text" value="' + parte[1] + '" name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off"  data-pre="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un n??mero decimal positivo" onkeyup="modificar_precio(\'' + id_producto + '\'); calcular_importe(\'' + id_producto + '\')">';
				<?php
                }
	            ?>			
    			
				plantilla = plantilla + '<input type="hidden" value="N" readonly name="edit[]" class="form-control input-xs text-right" autocomplete="off" data-precioedit="">';
				plantilla = plantilla + '<input type="hidden" value="' + asignation[0] + '" readonly name="asignaciones[]" class="form-control input-xs text-right" autocomplete="off" data-asignacion="' + asignation[0] + '"   data-validation-error-msg="Debe ser un n??mero decimal positivo">' +
				'</td>';
		}

		plantilla = plantilla + 
			'<td class="text-nowrap text-middle text-right" data-importe="">0.00</td>' +
			'<td class="text-nowrap text-middle text-center">' +
			'<button type="button" class="btn btn-success" tabindex="-1" onclick="eliminar_producto(\'' + id_producto + '\')"><span class="glyphicon glyphicon-trash"></span></button>' +
			'</td>' +
			'</tr>';

		$ventas.append(plantilla);

		$ventas.find('[data-cantidad], [data-precio], [data-descuento]').on('click', function() {
			$(this).select();
		});

		$ventas.find('[data-xxx]').on('change', function() {
            var v = $(this).find('option:selected').attr('data-yyy');
            var pr = $(this).find('option:selected').attr('data-pr');
			
			var st = $(this).find('option:selected').attr('data-xyyz');

			$(this).parent().parent().find('[data-precio]').val(v);
			$(this).parent().parent().find('[data-precio]').attr('data-precio',v);

			var z = $(this).find('option:selected').attr('data-yyz');
			var x = $.trim($('[data-stock2=' + pr + ']').text());
			var ze = Math.trunc(x / z);
			var zt = Math.trunc(st / z);
			$.trim($('[data-stock=' + pr + ']').text(ze));
			$(this).parent().parent().find('[data-cantidad]').attr('data-validation-allowing', 'range[1;' + zt + ']');
			$(this).parent().parent().find('[data-cantidad]').attr('data-validation-error-msg', 'Debe ser un n??mero positivo entre 1 y ' + zt);
			//console.log($(this).parent().parent().find('[data-cantidad]').attr('data-validation-allowing'));
			// descontar_precio(id_producto);
			calcular_importe(id_producto);
		});

		$ventas.find('[title]').tooltip({
			container: 'body',
			trigger: 'hover'
		});

		$.validate({
			form: '#formulario',
			modules: 'basic',
			onSuccess: function() {
				guardar_nota();
			}
		});
	//}



		calcular_importe(id_producto);
	}
	/////////////////////////////////////////////////////////////////////////////
	document.getElementById('forma_pago').addEventListener('change', () => {
		calcular_descuentoF();
	});

	function calcular_descuentoF() {
		let filas = document.getElementById('ventas').children[2].children;
		for (let i = 0; i < filas.length; ++i) {
			let cantidad = filas[i].children[3].children[0],
				precio = filas[i].children[5].children[0],
				importe = filas[i].children[7].innerText,
				descuento = filas[i].children[1].children[0].value;
			let subtotal = (parseFloat(precio.value) * parseFloat(cantidad.value)).toFixed(2);
			let porcentaje = ((100 - parseFloat(descuento)) / 100).toFixed(2);
			if (descuento !== '0' && document.getElementById('forma_pago').value === '1')
				subtotal = (subtotal * porcentaje).toFixed(2);
			filas[i].children[7].innerText = subtotal;
		}
		calcular_total();
	}
	/////////////////////////////////////////////////////////////////////////////

	function eliminar_producto(id_producto) {
		bootbox.confirm('Est?? seguro que desea eliminar el producto?', function(result) {
			if (result) {
				$('[data-producto=' + id_producto + ']').remove();
				renumerar_productos();
				calcular_total();
				calcular_descuento();
			}
		});
	}

	function renumerar_productos() {
		var $ventas = $('#ventas tbody');
		var $productos = $ventas.find('[data-producto]');
		$productos.each(function(i) {
			$(this).find('td:first').text(i + 1);
		});
	}

	function descontar_precio(id_producto) {
		var $producto = $('[data-producto=' + id_producto + ']');
		var $precio = $producto.find('[data-precio]');
		var $descuento = $producto.find('[data-descuento]');
		var precio, descuento;

		precio = $.trim($precio.attr('data-precio'));
		precio = ($.isNumeric(precio)) ? parseFloat(precio) : 0;
		descuento = $.trim($descuento.val());
		descuento = ($.isNumeric(descuento)) ? parseFloat(descuento) : 0;
		precio = precio - (descuento);
//		$precio.val(precio.toFixed(2));

		calcular_importe(id_producto);
	}

	$("#forma_pago").on('change', function() {
		var $ventas = $('#ventas tbody');
		var $productos = $ventas.find('[data-producto]');
		$productos.each(function(i) {
			// console.log($(this).attr("data-producto"));
			prod = $(this).attr("data-producto");
			calcular_importe(prod);
		});
	});


	function modificar_precio(id_producto) {
		var $producto = $('[data-producto=' + id_producto + ']');
		var $precioedit = $producto.find('[data-precioedit]');
		$precioedit.val("S");
    }
    function calcular_importe(id_producto) {
		// console.log(id_producto);
		var $producto = $('[data-producto=' + id_producto + ']');
		var $cantidad = $producto.find('[data-cantidad]');
		var $precio = $producto.find('[data-precio]');
		var $precioedit = $producto.find('[data-precioedit]');
		var $descuento = $producto.find('[data-descuento]');
        var $importe = $producto.find('[data-importe]');

		// Josema:: add
		var cantidad, precio, importe, fijo, descuento;

		fijo = $descuento.attr('data-descuento');
		fijo = ($.isNumeric(fijo)) ? parseFloat(fijo) : 0;
		cantidad = $.trim($cantidad.val());
		cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
		precio = $.trim($precio.val());
		precio = ($.isNumeric(precio)) ? parseFloat(precio) : 0.00;
		descuento = $.trim($descuento.val());
		descuento = ($.isNumeric(descuento)) ? parseFloat(descuento) : 0;
		precioedit = $.trim($precioedit.val());

        var $auxiliarP, $auxiliarC, ant_pre;
		ant_pre = $producto.find('[data-pre]').val();
		unidad = $producto.find('[data-unidad]').val();
		// console.log(unidad);

		V_producto_simple=id_producto.split("_");
		id_producto_simple=V_producto_simple[0];
		
		forma_pago = $('#forma_pago').val();

		var parameter = {
			'id_producto' : id_producto_simple,
			'unidad' : unidad,
			'cantidad' : cantidad,
			'forma_pago': forma_pago
		};
		
		$.ajax({
			url: "?/productos/precio",
			type: "POST",
			data: parameter,
			success: function( data ){
				// console.log(data);
				// a = $.parseJSON(data);
				<?php
                //if($_user['rol'] != 'Superusuario' && precioedit!="S") {
	            ?>
    			if(precioedit!="S") {
	            	$auxiliarP = data['precio_mayor'];
        			$auxiliarC = data['cantidad'];
    				// Asignamos el nuevo precio
    				$producto.find('[data-precio]').val($auxiliarP);
                }else{
	                $auxiliarP = precio;
    			}
				importe = (cantidad * $auxiliarP) - descuento;
				importe = importe.toFixed(2);
				$importe.text(importe);
	            
	            
				calcular_total();
			}
			// ,
			// error: function( error ){
			// 	console.log(error);
			// 	$('#loader').fadeOut(100);
			// 	$.notify({
			// 		message: 'No se pudo verificar los precios.'
			// 	}, {
			// 		type: 'danger'
			// 	});
			// }
		});
	}

	function calcular_total() {
	    SwGuardar=false;
        
		var $ventas = $('#ventas tbody');
		var $total = $('[data-subtotal]:first');
		var $importes = $ventas.find('[data-importe]');
		var importe, total = 0;

		$importes.each(function(i) {
			importe = $.trim($(this).text());
			importe = parseFloat(importe);
			total = total + importe;
		});

		$total.text(number_format(total ,2, ',','.'));
		$('[data-ventas]:first').val($importes.size()).trigger('blur');
		$('[data-total]:first').val(total.toFixed(2)).trigger('blur');
		calcular_descuento_total();
		if(SwActualizarCuotas){
		    set_cuotas();
		}
	}

	function tipo_descuento() {
		var descuento = $('#tipo').val();
		$('#descuento_bs').val('');
		$('#descuento_porc').val('');
		console.log(descuento);
		if (descuento == 0) {
			console.log(0);
			$('#div-descuento').hide();
			// $("input").prop('disabled', true);
		} else if (descuento == 1) {
			console.log(1);
			$('#div-descuento').show();
			// $("input").prop('disabled', true);
		}
		//calcular_descuento();
	}

	function calcular_descuento() {
		var $ventas = $('#ventas tbody');
		var $total = $('[data-subtotal]:first');
		var $importes = $ventas.find('[data-importe]');

		var descuento = $('#descuento_porc').val();
		console.log(descuento);

		var importe, total = 0;

		$importes.each(function(i) {
			importe = $.trim($(this).text());
			importe = parseFloat(importe);
			total = total + importe;
		});
		$total.text(number_format(total ,2 ,',', '.'));
		var importe_total = total.toFixed(2);
		//console.log(importe_total);

		var total_descuento = 0,
			formula = 0,
			total_importe_descuento = 0;
		console.log(descuento + 'jhfhgdghd');

		if (descuento == null) {
			var descuento_bs = $('#descuento_bs').val();
			console.log(descuento_bs + 'vacio0000');
			total_importe_descuento = parseFloat(importe_total) - parseFloat(descuento_bs);

			$('#importe_total_descuento').html(total_importe_descuento.toFixed(2));
			$('#total_importe_descuento').val(total_importe_descuento.toFixed(2));

		} else if (descuento == 0) {
			var descuento_bs = $('#descuento_bs').val();
			console.log(descuento_bs + 'vacio0000');
			total_importe_descuento = parseFloat(importe_total) - parseFloat(descuento_bs);

			$('#importe_total_descuento').html(total_importe_descuento.toFixed(2));
			$('#total_importe_descuento').val(total_importe_descuento.toFixed(2));

		} else if (descuento != "") {
			//console.log(descuento+'dif vacio');
			//var total_descuento=0, formula=0, total_importe_descuento=0;
			//total_descuento=descuento*100;
			//formula=(descuento/importe_total)*100;
			formula = (descuento / 100) * importe_total;

			total_importe_descuento = parseFloat(importe_total) - parseFloat(formula);

			$('#descuento_bs').val(formula.toFixed(2));
			$('#importe_total_descuento').html(total_importe_descuento.toFixed(2));
			$('#total_importe_descuento').val(total_importe_descuento.toFixed(2));
		}
	}
	function SetGuardar(event){
	    SwGuardar=true;
    }
	function guardar_nota() {
	    if(SwGuardar==true){
    	    bootbox.confirm('?0?7Desea guardar la venta?', function(result) {
    			if (result) {
            		var data = $('#formulario').serialize();
            		// console.log(data)
            		$('#loader').fadeIn(100);
            
            		$.ajax({
            			url: '?/notas/guardar_editar',
            			dataType: 'json',
            			type: 'post',
            			contentType: 'application/x-www-form-urlencoded',
            			data: data,
            			success: function( result ){
            				console.log(result);
            				$.notify({
            					message: 'La nota de remisi??n fue realizada satisfactoriamente.'
            				}, {
            					type: 'success',
            					delay: 50000,
            					timer: 60000,
            				});
            				if($('#distribuir').val()=="N"){
                				imprimir_nota(result.egreso_id, result.recibo);
            				}
            				else{
            				   window.location.reload(); 
            				}
            				// imprimir_nota(result.egreso_id);
                            // window.location.reload();
            			},
            			error: function( error ){
            				console.log(error);
            				$('#loader').fadeOut(100);
            				$.notify({
            					message: 'Ocurri?? un problema en el proceso, no se puedo guardar los datos de la nota de remisi??n, verifique si la se guard?? parcialmente.'
            				}, {
            					type: 'danger'
            				});
            			}
            		});
    			}else{
    			    SwGuardar=false;
    			}
    	    });
	    }
	}
	
    function imprimir_nota(nota,recibo) {
		bootbox.confirm('?0?7Desea imprimir la Nota de venta?', function(result) {
			if (result) {
		        $.open('?/notas/imprimir_nota/' + nota, true);
		        if(recibo == 'si'){
				    imprimir_recibo(nota);
				}
			}
			else{
	    		imprimir_recibo(nota);
			}
		});
	}
    function imprimir_recibo(nota) {
		bootbox.confirm('?0?7Desea Imprimir el recibo?', function(result) {
			if (result) {
        		window.open('?/notas/recibo_dinero/' + nota, true);
        		window.location.href="?/notas/mostrar";
			}
			else{
        		window.location.href="?/notas/mostrar";
			}
		});
	}
	
	function vender(elemento) {
		var $elemento = $(elemento),
			vender;
		vender = $elemento.attr('data-vender');
		adicionar_producto(vender);
	}

	//cuentas

	var $inicial_fecha = new Array();
	for (i = 1; i < 36; i++) {
		$inicial_fecha[i] = $('#inicial_fecha_' + i + '');
		$inicial_fecha[i].datetimepicker({
			//format: formato
			format: 'DD-MM-YYYY',
			minDate: '<?= date('Y-m-d') ?>'
		});
	}

	function set_cuotas() {
		if(SwActualizarCuotas){
    		var cantidad = $('#nro_cuentas').val();
    		var $compras = $('#cuentasporpagar tbody');
    
    		$("#nro_plan_pagos").val(cantidad);
    
    		if (cantidad > 36) {
    			cantidad = 36;
    			$('#nro_cuentas').val("36")
    		}
    		for (i = 1; i <= cantidad; i++) {
    			$('[data-cuota=' + i + ']').css({
    				'height': 'auto',
    				'overflow': 'visible'
    			});
    			$('[data-cuota2=' + i + ']').css({
    				'margin-top': '10px;'
    			});
    			$('[data-cuota=' + i + ']').parent('td').css({
    				'height': 'auto',
    				'border-width': '1px',
    				'padding': '5px'
    			});
    		}
    		for (i = parseInt(cantidad) + 1; i <= 36; i++) {
    			$('[data-cuota=' + i + ']').css({
    				'height': '0px',
    				'overflow': 'hidden'
    			});
    			$('[data-cuota2=' + i + ']').css({
    				'margin-top': '0px;'
    			});
    			$('[data-cuota=' + i + ']').parent('td').css({
    				'height': '0px',
    				'border-width': '0px',
    				'padding': '0px'
    			});
    		}
    		set_cuotas_val();
    		calcular_cuota(1000);
		}
	}

	function set_cuotas_val() {
		nro = $('#nro_cuentas').val();
		valorG = parseFloat($('[data-total]:first').val());
		
	    nro_pagos=<?= $nrocuotasX['nro_pagos'] ?>;
        monto_pagado=<?= $nrocuotasX['monto_pagado'] ?>;

		valor = (valorG-monto_pagado) / (nro-nro_pagos);
		
		for(i = nro_pagos+1; i <= nro; i++){
			if (i == nro) {
				final = (valorG-monto_pagado) - (valor.toFixed(1) * (i - nro_pagos - 1));
				$('[data-cuota=' + i + ']').children('.monto_cuota').val(final.toFixed(1) + "0");
			} else {
				$('[data-cuota=' + i + ']').children('.monto_cuota').val(valor.toFixed(1) + "0");
			}
		}
	}

	function set_plan_pagos() {
		if ($("#forma_pago").val() == 1) {
			$('#plan_de_pagos').css({
				'display': 'none'
			});
			if ($('#nro_cuentas').val() <= 0) {
				$('#nro_cuentas').val('1');
				calcular_cuota(1000);
				$("#nro_plan_pagos").val('1');
			}
		} else {
			$('#plan_de_pagos').css({
				'display': 'block'
			});
		}
	}

	function calcular_cuota(x) {
	    //alert(x);
		if(SwActualizarCuotas){
    		var cantidad = $('#nro_cuentas').val();
    		var nro = $('#nro_cuentas').val();
    		var total = 0;
    
    		for (i = 1; i <= x && i <= cantidad; i++) {
    			importe = $('[data-cuota=' + i + ']').children('.monto_cuota').val();
    			importe = parseFloat(importe);
    			total = total + importe;
    		}
    		
    		//alert(total);
    		
    		valorTotal = parseFloat($('[data-total]:first').val());
    		if (nro > x) {
    			valor = (valorTotal - total) / (nro - x);
    		} else {
    			valor = 0;
    		}
    
            //alert(valor);
    
    		for (i = (parseInt(x) + 1); i <= cantidad; i++) {
    			if (valor >= 0) {
    				if (i == cantidad) {
    					valor = valorTotal - total;
    					$('[data-cuota=' + i + ']').children('.monto_cuota').val(valor.toFixed(1) + "0");
    				} else {
    					$('[data-cuota=' + i + ']').children('.monto_cuota').val(valor.toFixed(1) + "0");
    				}
    				total = total + (valor.toFixed(1) * 1);
    			} else {
    				$('[data-cuota=' + i + ']').children('.monto_cuota').val("0.00");
    			}
    		}
    
    		$('[data-totalcuota]').text(total.toFixed(1) + "0");
    		valor = parseFloat($('[data-subporcentaje]:first').text());
    		if (valor == total.toFixed(1) + "0") {
    			$('[data-total-pagos]:first').val(1);
    			$('[data-total-pagos]:first').parent('div').children('#monto_plan_pagos').attr("data-validation-error-msg", "");
    		} else {
    			$('[data-total-pagos]:first').val(0);
    			$('[data-total-pagos]:first').parent('div').children('#monto_plan_pagos').attr("data-validation-error-msg", "La suma de las cuotas es diferente al costo total ?0?0 " + total.toFixed(1) + "0" + " / " + valor.toFixed(1) + "0" + " ?0?3");
    		}
    		
    		//alert("xxxxxxxxxxxxxxx");
		}
	}

	function change_date(x) {
		if ($('#inicial_fecha_' + x).val() != "") {
			if (x < 36) {
				$('#inicial_fecha_' + (x + 1)).removeAttr("disabled");
			}
		} else {
			for (i = x; i <= 35; i++) {
				$('#inicial_fecha_' + (i + 1)).val("");
				$('#inicial_fecha_' + (i + 1)).attr("disabled", "disabled");
			}
		}
	}

	function setPago() {
		$('#data-tipo-pago').val(2);
	}

	function calcular_descuento_total() {
		var $ventas = $('#ventas tbody');
		var $total = $('[data-subtotal]:first');
		var $importes = $ventas.find('[data-importe]');
		var descuento = $('#descuento_porc').val();
		var importe, total = 0;
		$importes.each(function(i) {
			importe = $.trim($(this).text());
			importe = parseFloat(importe);
			total = total + importe;
		});
		$total.text(number_format(total , 2, ',', '.'));
		var importe_total = total.toFixed(2);
		var total_descuento = 0,
			formula = 0,
			total_importe_descuento = 0;
		if (descuento == null || descuento == 0 || descuento == '') {
			$('#descuento_porc').val(0);
			descuento = 0;
			var descuento_bs = $('#descuento_bs').val();
			if (descuento_bs.trim() == '')
				descuento_bs = 0;
			total_importe_descuento = parseFloat(importe_total) - parseFloat(descuento_bs);
			$('#importe_total_descuento').html(total_importe_descuento.toFixed(2));
			$('#total_importe_descuento').val(total_importe_descuento.toFixed(2));
		} else {
			formula = (descuento / 100) * importe_total;
			total_importe_descuento = parseFloat(importe_total) - parseFloat(formula);
			$('#descuento_bs').val(0);
			$('#importe_total_descuento').html(total_importe_descuento.toFixed(2));
			$('#total_importe_descuento').val(total_importe_descuento.toFixed(2));
		}
	}

	function descuento_pago_pristine(dato = [0, 0]) {
		//Ocultar o Mostrar en base al cliente seleccionado
		if (dato[0] == 0) {
			document.getElementById('estado_descuentoF').classList.add('hidden');
			$('#descuentoGrupoF').val(0);
		} else {
			document.getElementById('estado_descuentoF').classList.remove('hidden');
			$('#descuentoGrupoF').val(dato[0]);
		}
		if (dato[1] == 0){
			// document.getElementById('CreditoF').classList.add('hidden');
		} else
			document.getElementById('CreditoF').classList.remove('hidden');
		//Reiniciar la configuracion
		tipo_descuento();
	}

	function tipo_descuento() {
		var descuento = $('#tipo').val();
		if (descuento == 0) {
			$('#div-descuento').hide();
			$('#div-descuento2').show();
			$('#descuento_bs').val($('#descuentoGrupoF').val());
			$('#descuento_porc').val(0);
		} else if (descuento == 1) {
			$('#div-descuento').show();
			$('#div-descuento2').hide();
			$('#descuento_bs').val(0);
			$('#descuento_porc').val($('#descuentoGrupoF').val());
		}
		calcular_descuento_total();
	}


	function sidenav(){
		let contenedor=document.getElementById('ContenedorF');
		if(contenedor.children[0].classList.contains('col-md-6')){
			contenedor.children[0].classList.remove('col-md-6');
			contenedor.children[0].classList.add('col-md-12');
			contenedor.children[1].classList.add('hidden');
		}
		else{
			contenedor.children[0].classList.remove('col-md-12');
			contenedor.children[0].classList.add('col-md-6');
			contenedor.children[1].classList.remove('hidden');
		}
	}

	function mostrar_datos(id_cliente){
		var parameter = {
			'id_cliente' : id_cliente
		};

		$.ajax({
			url: '?/clientes/historial',
			type: 'POST',
			data: parameter,
			dataType: 'json',
			success: function(data){
				$('#compras_cliente').html(data.basico);
				if(data.avanzado) {
					$('#para_deudas').removeClass('hidden');
					$('#historial_deudas').html(data.avanzado);
				} else {
					$('#para_deudas').addClass('hidden');
				}
			}
		});
	}

	function Ftipo_pago(){
		var tipo = $('#tipo_pago').val();
		var n_f = $('#nro_factura').val();
		console.log(n_f);
		if (tipo == 'EFECTIVO') {
			$('#nro_pago').css({'display':'none'});
			$('#nro_pago').val(n_f);
		} else  {
			$('#nro_pago').css({'display':'block'});
			$('#nro_pago').attr('placeholder', 'Ingrese el Nro. de transaccion');
			$('#nro_pago').val('');
		}
	}
	//////////////////////////////////////////////////////////////////////
	function seleccionar_vendedor(id_vendedor){
		$('#empleado option')
			.removeAttr('selected')
				.filter('[value="'+id_vendedor+'"]')
					.attr('selected', true).change();
		$('#empleado').val(id_vendedor);
	}
	/////////////////////////////////////////////////////////////////////
	$("#forma_pago").change(function(){
      if($('#forma_pago').val() == 2) {
          $('#para_pagos').addClass('hidden');
      } else {
          $('#para_pagos').removeClass('hidden');
      }
    });

</script>
<?php require_once show_template('footer-empty'); ?>