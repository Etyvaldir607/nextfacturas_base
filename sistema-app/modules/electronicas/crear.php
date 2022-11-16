<?php
$formato_textual = get_date_textual($_institution['formato']);

if ($params[0] != '')
	$almacen = $db->from('inv_almacenes')->where('id_almacen=', 1)->fetch_first();

$id_almacen = 1;

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
            			        (cg.vendedor_id='" . $_user['persona_id'] . "' AND '" . $_user['rol_id'] . "' = 15)
            			        OR 
            			        (ss.user_ids='" . $_user['id_user'] . "' AND '" . $_user['rol_id'] . "' = 14)
            			        OR 
            			        '" . $_user['rol_id'] . "' = 1
            			    )
						ORDER BY c.cliente ASC,c.nit ASC")->fetch();

$productosquery = "SELECT p.id_producto, p.asignacion_rol, p.descuento ,p.promocion,
						z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2,
						p.descripcion,p.imagen,p.codigo,p.nombre_factura as nombre,p.nombre_factura,p.cantidad_minima,p.precio_actual,
						IFNULL(e.cantidad_lote, 0) AS cantidad_ingresos, 
						u.unidad, u.sigla, c.categoria, e.vencimiento, e.lote,e.id_detalle, '' as id_detalle_productos, e.id_promocion_precio
						FROM inv_productos p
						LEFT JOIN (
							SELECT d.producto_id, SUM(d.lote_cantidad) AS cantidad_lote, d.vencimiento, d.lote, d.id_detalle, IFNULL(id_promocion_precio,0)as id_promocion_precio
							FROM inv_ingresos_detalles d
							LEFT JOIN inv_ingresos i ON i.id_ingreso = d.ingreso_id
							left join inv_promocion_precios pp on pp.producto_id=d.producto_id AND pp.lote=d.lote AND pp.vencimiento=d.vencimiento
                            WHERE i.almacen_id = '$id_almacen'
							GROUP BY d.producto_id, d.vencimiento, d.lote
						) AS e ON e.producto_id = p.id_producto
						
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
				ORDER BY nombre_factura, e.vencimiento ASC";

$productos = $db->query($productosquery)->fetch();

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
            			        (e.id_empleado='" . $_user['persona_id'] . "' AND '" . $_user['rol_id'] . "' = 15)
            			        OR 
            			        (ss.user_ids='" . $_user['id_user'] . "' AND '" . $_user['rol_id'] . "' = 14)
            			        OR 
            			        '" . $_user['rol_id'] . "' = 1
            			    )
            			    
						AND u.active = 1")->fetch();

//if($_user['rol'] == 'Superusuario' || $_user['rol'] == 'Administrador') { 

//WHERE r.id_rol != 4 diferente de repartidor
//##include siat functions
require_once dirname(__DIR__) . '/siat/siat.php';
$sucursal_id		= 0;
$puntoventa_id		= $params[0];

//var_dump($puntoventa_id);

$metodos_pago_siat 	= siat_tipos_metodos_pago($sucursal_id, $puntoventa_id);
$tipos_documentos	= siat_tipos_documento_identidad($sucursal_id, $puntoventa_id);
$eventos			= siat_tipos_eventos($sucursal_id, $puntoventa_id);
$eventoActivo		= siat_eventos_obtener_activo($sucursal_id, $puntoventa_id);

require_once show_template('header-empty'); ?>
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
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title">
					<span class="glyphicon glyphicon-option-vertical"></span>
					<strong>Ventas Facturada</strong>
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
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-option-vertical"></span>
						<strong>Ventas Facturadas</strong>
					</h3>
				</div>
				<div class="panel-body">
					<div class="">
						<div class="btn-group">
							<?php if (!$eventoActivo) : ?>
								<button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									En Linea<span class="caret"></span>
								</button>
								<ul class="dropdown-menu">
									<!-- <?php //foreach($eventos->RespuestaListaParametricas->listaCodigos as $evt): if( $evt->codigoClasificador > 4 ) continue; 
											?> -->
									<?php foreach ($eventos->RespuestaListaParametricas->listaCodigos as $evt) : if ($evt->codigoClasificador > 4) continue; ?>
										<li>
											<!-- <a href="?/siat/eventos/<?php //print $evt->codigoClasificador 
																			?>/crear/<?php //print $sucursal_id 
																						?>/<?php //print $puntoventa_id 
																							?>">
								 		<?php //print $evt->descripcion 
											?>
								 	</a>  -->

											<a href="javascript:void(0)" onclick="crear_evento({
																						'evento_id'		: <?php print intval($evt->codigoClasificador); ?>,
																						'descripcion'	: '<?php print $evt->descripcion; ?>',
																						'sucursal_id'	: <?php print intval($sucursal_id); ?>,
																						'puntoventa_id'	: <?php print intval($puntoventa_id); ?>
																					  })">
												<?php print $evt->descripcion ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<button type="button" class="btn btn-danger" onclick="cerrar_evento({
																						'id_evento'		: <?php print intval($eventoActivo->id); ?>
																					  })">Fuera de linea
								</button>
								<!-- <a href="?/siat/eventos/<?php //print $eventoActivo->id 
																?>/cerrar" class="btn btn-danger">Fuera de linea</a> -->
							<?php endif; ?>

						</div>
					</div>
					<h2 class="lead text-primary">Ventas Facturadas : <?= escape($almacen['almacen']); ?></h2>
					<hr>
					<form id="formulario" class="form-horizontal">
						<input name="puntoventa_id" type="hidden" value="<?= escape($puntoventa_id); ?>">
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
								<input type="hidden" name="id_cliente" id="id_cliente" value="" />
							</div>
						</div>

						<div class="form-group">
							<label for="almacen" class="col-sm-4 control-label">Señor(es):</label>
							<div class="col-sm-8 right">
								<div class="input-group">
									<input type="text" value="" name="nombre_cliente" id="nombre_cliente" class="form-control text-uppercase" autocomplete="off" data-validation="required">
									<!--data-validation="required letternumber length" data-validation-allowing="-+./&() " data-validation-length="max100"-->
									<span class="input-group-addon" style="padding-top: 0px; padding-bottom: 0px;">
										<button type="button" id="ver_datos" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#modalResumenVentas" onclick="mostrar_datos(getElementById('id_cliente').value);">
											Historial
										</button>
									</span>
								</div>
							</div>
						</div>
						<!--<div class="form-group">-->
						<!--	<label for="nit_ci" class="col-sm-4 control-label">NIT / CI:</label>-->
						<!--	<div class="col-sm-8">-->
						<!--data-validation="required number"-->
						<!--	</div>-->
						<!--</div>-->
						<div class="form-group">
							<label for="ciudad" class="col-sm-4 control-label">Ciudad:</label>
							<div class="col-sm-8">
								<input type="text" value="" name="ciudad" id="ciudad" class="form-control text-uppercase" autocomplete="off">
							</div>
						</div>
						<div class="form-group">
							<label for="direccion" class="col-sm-4 control-label">Dirección:</label>
							<div class="col-sm-8">
								<input type="text" value="" name="direccion" id="direccion" class="form-control text-uppercase" autocomplete="off"> <!-- data-validation="required " -->
							</div>
						</div>
						<div class="form-group">
							<label for="empleado" class="col-sm-4 control-label">Asignar vendedor:</label>
							<div class="col-sm-8">
								<select name="empleado" id="empleado" data-empleado class="form-control text-uppercase" data-validation="required" data-validation-allowing="-+./&()">
									<option value="">Buscar....</option>
									<?php foreach ($empleados as $empleado) { ?>
										<option value="<?= escape($empleado['id_empleado']); ?>" <?= ($empleado['id_user'] == $_user['id_user']) ? 'selected="selected"' : '' ?>><?= escape($empleado['codigo'] . ' - ' . $empleado['empleado']); ?></option>
									<?php } ?>
								</select>
							</div>
						</div>


						<?php // if ($_user['rol'] == 'Superusuario' || $_user['rol'] == 'Administrador') { 
						?>
						<div class="form-group" id='CreditoF'>
							<label for="almacen" class="col-sm-4 control-label">Forma de Pago:</label>
							<div class="col-sm-8">
								<select name="forma_pago" id="forma_pago" class="form-control" data-validation="required number" onchange="set_plan_pagos();">
									<option value="1">Contado</option>
									<option value="2">Plan de Pagos</option>
								</select>
							</div>
						</div>
						<?php // } 
						?>
						<input type="number" class="hidden" id="nro_factura" value="<?= $nro_factura ?>">
						<div class="form-group" id="para_pagos">
							<label for="almacen" class="col-sm-4 control-label">Tipo de Pago:</label>
							<div class="col-sm-8">
								<div class="row">
									<div class="col-md-5">
										<select name="tipo_pago" id="tipo_pago" class="form-control" onchange="Ftipo_pago()">
											<option value="EFECTIVO">Efectivo</option>
											<option value="CHEQUE">Cheque</option>
											<option value="TRANSFERENCIA">Transferencia</option>
											<option value="TARJETA">Tarjeta</option>
										</select>
									</div>
									<div class="col-md-7">
										<input type="text" name="nro_pago" id="nro_pago" value="<?= $nro_factura ?>" placeholder="Nro documento" class="form-control" autocomplete="off" data-validation="number" aria-label="..." data-validation-optional="true">
									</div>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-4 control-label">Tipo Documento Identidad SIAT</label>
							<div class="col-sm-8">
								<select name="tipo_documento_identidad" class="form-control" required>
									<?php foreach ($tipos_documentos->RespuestaListaParametricas->listaCodigos as $mp) : ?>
										<option value="<?php print $mp->codigoClasificador ?>">
											<?php print $mp->descripcion ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

						</div>
						<div class="form-group">
							<label class="col-sm-4 control-label">Nro. Documento Identidad SIAT</label>
							<div class="col-sm-5">
								<input type="text" value="" name="nit_ci" id="nit_ci" class="form-control text-uppercase" autocomplete="off" required />
							</div>
							<div class="col-sm-3">
								<input type="text" name="complemento" value="" class="form-control" placeholder="Complemento" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-4 control-label">Metodos de Pago SIAT</label>
							<div class="col-sm-8">
								<select name="codigo_metodo_pago" class="form-control" required>
									<?php foreach ($metodos_pago_siat->RespuestaListaParametricas->listaCodigos as $mp) : ?>
										<option value="<?php print $mp->codigoClasificador ?>">
											<?php print $mp->descripcion ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-4 control-label">Numero Tarjeta SIAT</label>
							<div class="col-sm-8">
								<input type="text" name="numero_tarjeta" value="" class="form-control" />
							</div>
						</div>
						<div class="form-group">
							<label for="almacen" class="col-sm-4 control-label">Tipo de Entrega:</label>
							<div class="col-sm-8">
								<div class="row">
									<div class="col-md-12">
										<select name="distribuir" id="distribuir" class="form-control">
											<option value="N">Entrega Inmediata</option>
											<option value="S">Distribucion</option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="form-group">
							<label for="observacion" class="col-sm-4 control-label">Observación:</label>
							<div class="col-sm-8">
								<textarea name="observacion" id="observacion" class="form-control text-uppercase" rows="2" autocomplete="off" data-validation="letternumber" data-validation-allowing='-+/.,:;@#&"()_\n ' data-validation-optional="true"></textarea>
							</div>
						</div>

						<!-- Button trigger modal -->


						<div class="table-responsive margin-none">
							<table id="ventas" class="table table-bordered table-condensed table-striped table-hover table-xs text-sm ">
								<thead>
									<tr class="active">
										<th class="text-nowrap text-center">#</th>
										<th class="text-nowrap text-center">CÓDIGO</th>
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
								<tbody></tbody>
							</table>
						</div>
						<div class="form-group">
							<div class="col-xs-12">
								<input type="text" name="almacen_id" value="<?= $almacen['id_almacen']; ?>" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="El almacén no esta definido">
								<input type="text" name="nro_registros" value="0" class="translate" tabindex="-1" data-ventas="" data-validation="required number" data-validation-allowing="range[1;<?= $limite_longitud; ?>]" data-validation-error-msg="El número de productos a vender debe ser mayor a cero y menor a <?= $limite_longitud; ?>">
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
											<input type="text" value="1" id="nro_cuentas" name="nro_cuentas" class="form-control text-right" autocomplete="off" data-cuentas="" data-validation="required number" data-validation-allowing="range[1;360],int" data-validation-error-msg="Debe ser número entero positivo" onkeyup="set_cuotas()">
										</div>
									</div>

									<table id="cuentasporpagar" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
										<thead>
											<tr class="active">
												<th class="text-nowrap text-center col-xs-4">Detalle</th>
												<th class="text-nowrap text-center col-xs-4">Fecha de Pago</th>
												<th class="text-nowrap text-center col-xs-4">Monto</th>
											</tr>
										</thead>
										<tbody>
											<?php for ($i = 1; $i <= 36; $i++) { ?>
												<tr class="active cuotaclass">
													<!--<?php //if ($i == 1) { 
														?>-->
													<!--	<td class="text-nowrap" valign="center">-->
													<!--		<div data-cuota="<?= $i ?>" data-cuota2="<?= $i ?>" class="cuota_div">Pago Inicial:</div>-->
													<!--	</td>-->
													<!--<?php //} else { 
														?>-->
													<td class="text-nowrap" valign="center">
														<div data-cuota="<?= $i ?>" data-cuota2="<?= $i ?>" class="cuota_div">Cuota <?= $i ?>:</div>
													</td>
													<?php //} 
													?>

													<td>
														<div data-cuota="<?= $i ?>" class="cuota_div">
															<div class="col-sm-12">
																<input id="inicial_fecha_<?= $i ?>" name="fecha[]" value="<?= date('d-m-Y') ?>" min="<?= date('d-m-Y') ?>" class="form-control input-sm" autocomplete="off" <?php if ($i == 1) { ?> data-validation="required date" <?php } ?> data-validation-format="DD-MM-YYYY" data-validation-min-date="<?= date('d-m-Y'); ?>" onchange="javascript:change_date(<?= $i ?>);" onblur="javascript:change_date(<?= $i ?>);" <?php if ($i > 1 && false) { ?> disabled="disabled" <?php } ?>>
															</div>
														</div>
													</td>
													<td>
														<div data-cuota="<?= $i ?>" class="cuota_div"><input type="text" value="0" name="cuota[]" class="form-control input-sm text-right monto_cuota" maxlength="7" autocomplete="off" data-montocuota="0" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="Debe ser número decimal positivo" onchange="javascript:calcular_cuota('<?= $i ?>');"></div>
													</td>
												</tr>
											<?php } ?>
										</tbody>
										<tfoot>
											<tr class="active">
												<th class="text-nowrap text-center" colspan="2">Importe total <?= escape($moneda); ?></th>
												<th class="text-nowrap text-right" data-totalcuota="">0.00</th>
											</tr>
										</tfoot>
									</table>
									<br>
								</div>




								<!--div class="col-xs-12 text-left">
									<label for="almacen" class="col-md-5 control-label">Venta empleado:</label>
									<div class="col-md-7 right">
										<div class="input-group">
											<span class="input-group-addon">
												<input type="checkbox" name="reserva" style="display:none">
											</span>
											<input type="text" name="des_reserva" placeholder="Motivo" class="form-control">
										</div>
									</div>
								</div>
								<p>&nbsp;</p-->

								<div class="col-xs-6 text-right">
									<button type="submit" class="btn btn-primary" onmouseup="SetGuardar(event);">Guardar</button>
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
			<div class="panel panel-primary" data-servidor="<?= ip_local . name_project . '/nota.php'; ?>">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-menu-hamburger"></span>
						<strong>Información sobre la transacción</strong>
					</h3>
				</div>
				<div class="panel-body">
					<h2 class="lead text-primary">Información sobre la transacción</h2>
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
						<strong>Búsqueda de productos</strong>
					</h3>
				</div>
				<div class="panel-body">
					<h2 class="lead">Búsqueda de productos</h2>
					<hr>
					<?php if ($permiso_mostrar) : ?>
						<p class="text-right">
							<a href="?/electronicas/mostrar" class="btn btn-primary">Mis ventas facturadas</a>
						</p>
					<?php endif ?>

					<?php if ($productos) { ?>
						<table id="productosTable" class="table table-bordered table-condensed table-striped table-hover table-xs text-sm">
							<thead>
								<tr class="active">
									<th class="text-nowrap">Imagen</th>
									<th class="text-nowrap">Código</th>
									<th class="text-nowrap">Producto</th>
									<!--<th class="text-nowrap">Categoria</th>-->
									<th class="text-nowrap">Stock</th>
									<th class="text-nowrap">Precio</th>
									<th class="text-nowrap">Acciones</th>
									<th class="text-nowrap hidden"></th>
									<th class="text-nowrap hidden"></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$product_color = 0;
								foreach ($productos as $nro => $producto) {
									$otro_precio = $db->query("SELECT *
									FROM inv_asignaciones a
									LEFT JOIN inv_unidades b ON a.unidad_id=b.id_unidad
									WHERE a.producto_id='{$producto['id_producto']}'")->fetch();


									// echo json_encode($otro_precio);
									if (escape($producto['cantidad_ingresos']) > 0) {
								?>
										<tr data-busqueda="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>">
											<td class="text-nowrap text-middle text-center width-none" <?php
																										if ($product_color == $producto['id_producto']) {
																											echo ' style="background-color:rgb(128,30,30);" ';
																										} else {
																											$product_color = $producto['id_producto'];
																											echo ' style="background-color:rgb(60,128,60);" ';
																										}
																										?>>
												<img src="<?= ($producto['imagen'] == '') ? imgs . '/image.jpg' : files . '/productos/' . $producto['imagen']; ?>" class="img-rounded cursor-pointer" data-toggle="modal" data-target="#modal_mostrar" data-modal-size="modal-md" data-modal-title="Imagen" width="75" height="75">
											</td>
											<td class="text-nowrap text-middle" data-codigo="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>" data-codigo2=""><?= escape($producto['codigo']); ?></td>
											<td class="text-middle">
												<em><?= escape($producto['nombre']); ?></em>
												<br>
												<span class="vencimientoView" data-vencimientoview="">Venc: <?= escape(date_decode($producto['vencimiento'], $_institution['formato'])); ?></span>
												<br>
												<span class="loteView" data-loteview="">Lote: <?= escape($producto['lote']); ?></span>
												<span class="editar hidden" data-editar="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>"><?php
																																							if ($producto['id_promocion_precio'] == 0) {
																																								echo "N";
																																							} else {
																																								echo "S";
																																							}
																																							?></span>
												<span class="vencimiento hidden" data-vencimiento="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>">Venc: <?= escape(date_decode($producto['vencimiento'], $_institution['formato'])); ?></span>
												<span class="lote hidden" data-lote="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>">Lote: <?= escape($producto['lote']); ?></span>
												<span class="nombre_producto hidden" data-nombre="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>"><?= escape($producto['nombre']); ?></span>
											</td>
											<!--<td class="text-nowrap text-middle"><?= escape($producto['categoria']); ?></td>-->
											<td class="text-nowrap text-middle text-right" data-stock="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>"><?= escape($producto['cantidad_ingresos']); ?></td>
											<td class="text-middle text-right" data-asignacion="" style="font-weight: bold; font-size: 0.8em;">
												<?php echo escape($producto['precio_actual']); ?>
												<div data-valor="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>" style="display:none;">
													<?php
													echo '*(1)' . escape($producto['unidad']) . ': <b>' . escape($producto['precio_actual']) . '</b>';
													foreach ($otro_precio as $otro) {
													?>
														<br />*(<?= escape($producto['cantidad2']) . ')' . escape($otro['unidad'] . ': '); ?><b><?= escape($otro['otro_precio']); ?></b>
													<?php } ?>
												</div>
											</td>
											<td class="text-nowrap text-middle text-center width-none">
												<button type="button" class="btn btn-primary" data-vender="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>" onclick="vender(this);" title="Vender">
													<span class="glyphicon glyphicon-shopping-cart"></span>
													<!--Vender-->
												</button>
												<!--<button type="button" class="btn btn-default" data-actualizar="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>" onclick="actualizar(this, <?= $id_almacen ?>);calcular_descuento()" title="Actualizar">-->
												<!--<span class="glyphicon glyphicon-refresh"></span>-->
												<!--Actualizar-->
												<!--</button>-->
											</td>
											<td class="hidden" data-cant="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>"><?= escape($producto['cantidad2']); ?></td>
											<td class="hidden" data-stock2="<?= $producto['id_producto'] . '_' . $producto['id_detalle']; ?>"><?= escape($producto['cantidad_ingresos']); ?></td>
										</tr>



								<?php }  // fin if
								} // fin foreach 
								?>
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
						<p>Usted no puede realizar ventas facturadas, verifique que la siguiente información sea correcta:</p>
						<ul>
							<li>El almacén principal no esta definido, ingrese al apartado de "almacenes" y designe a uno de los almacenes como principal.</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
</div>
<h2 class="btn-primary position-left-bottom display-table btn-circle margin-all display-table" data-toggle="tooltip" data-title="Esto es una nota de remisión" data-placement="right">
	<span class="glyphicon glyphicon-star display-cell"></span>
</h2>

<!-- Plantillas filtrar inicio -->
<div id="tabla_filtrar" class="hidden">
	<div class="table-responsive">
		<table class="table table-bordered table-condensed table-striped table-hover">
			<thead>
				<tr class="active">
					<th class="text-nowrap text-middle text-center width-none">Imagen</th>
					<th class="text-nowrap text-middle text-center">Código</th>
					<th class="text-nowrap text-middle text-center">Producto</th>
					<th class="text-nowrap text-middle text-center">Categoría</th>
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
			<td class="text-middle text-right" data-valor="" data-asignacion="">
				<div class="stockpromocion hidden" data-stockpromocion=""></div>
			</td>
			<td class="text-nowrap text-middle text-center width-none">
				<button type="button" class="btn btn-primary" data-vender="" onclick="vender(this);">
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
	<div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
		<!-- style="max-width: 80em !important;" -->
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.27.2/axios.min.js"></script>

<script>
	axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
	// axios.defaults.headers.common['Content-Type'] = 'multipart/form-data';
	// axios.defaults.headers.common['Content-Type'] = 'application/json;charset=UTF-8';
	function crear_evento(el) {
		bootbox.confirm('Está seguro de ingresar en modo offline?', function(result) {
			if (result) {
				/*const resp = await*/
				axios.post(`?/siat/api_eventos/crear_evento`, el).then(({
					data
				}) => data);

				//window.location.reload();
			}
		});
	}

	function cerrar_evento(el) {
		bootbox.confirm('Ingresar modo online y enviar las facturas?', function(result) {
			if (result) {
				/*const resp = await*/
				axios.post(`?/siat/api_eventos/cerrar_evento`, el).then(({
					data
				}) => data);
			
				//window.location.reload();
			}
		});
	}

	async function guardar_nota(request) {

			var data = $('#formulario').serialize();

			for (let i = 1; i <= 1; i++) {

				const texto = await new Promise((resolve, reject) => {

					setTimeout(function() {
						$.ajax({
							url: '?/electronicas/guardar',
							dataType: 'json',
							type: 'post',
							contentType: 'application/x-www-form-urlencoded',
							data: data,
							success: function(result) {
								resolve("save");
							},
							error: function(error) {
								$.notify({
									message: 'Ocurrió un problema en el proceso, no se puedo guardar los datos de la nota de remisión, verifique si la se guardó parcialmente.'
								}, {
									type: 'danger'
								});
							}
						});
					}, 10); //1 para recibir error de xml
				});
			}
	}

</script>



<script>
	SwGuardar = false;

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

		$('#nro_pago').css({
			'display': 'none'
		});

		$('#productosTable_filter').children('label').children('.form-control').attr('id', 'id_buscador');
	});

	var idp;

	function SetEnter(event) {
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
		var $elemento = $(elemento),
			vender;
		vender = $elemento.attr('data-vender');
		adicionar_producto(vender);
	}

	$(function() {
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
		});
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
				//$nit_ci.prop('readonly', true);
				$nombre_cliente.prop('readonly', true);
				$telefono.prop('readonly', true);
				$direccion.prop('readonly', true);
				$ciudad.prop('readonly', true);
				$nit_ci.val(valor[0]);
				$id_cliente.val(valor[5]);
				$nombre_cliente.val(valor[1]);
				$telefono.val(valor[4]);
				$direccion.val(valor[2]);
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
				// $nit_ci.prop('readonly', false);
				// $nombre_cliente.prop('readonly', false);
				// $telefono.prop('readonly', false);
				// $direccion.prop('readonly', false);
				// $ciudad.prop('readonly', false);
				if (es_nit(valor[0])) {
					$nit_ci.val(valor[0]);
					$nombre_cliente.val('').focus();
				} else {
					$nombre_cliente.val(valor[0]);
					$nit_ci.val('').focus();
				}
			}
			if ($('#nombre_cliente').val() != '') {
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
			$('#ventas tbody').empty();
			// 			$nit_ci.prop('readonly', false);
			// 			$nombre_cliente.prop('readonly', false);
			// 			$direccion.prop('readonly', false);
			// 			$ciudad.prop('readonly', false);
			// 			$telefono.prop('readonly', false);
			// 			$ubicacion.prop('readonly', false);
			//$nit_ci.prop('readonly', true);
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
							console.log(productos[i].id_producto + '_' + productos[i].id_detalle);
							if (parseInt(productos[i].cantidad_ingresos) > 0) {
								productos[i].imagen = (productos[i].imagen == '') ? $fila_filtrar.attr('data-negativo') + 'image.jpg' : $fila_filtrar.attr('data-positivo') + productos[i].imagen;
								// productos[i].codigo = productos[i].codigo;
								$contenido_filtrar.find('tbody').append($fila_filtrar.html());
								$contenido_filtrar.find('tbody tr:last').attr('data-busqueda', productos[i].id_producto + '_' + productos[i].id_detalle);

								if (productos[i].promocion === 'si') {
									$ultimo = $contenido_filtrar.find('tbody tr:nth-last-child(1)').children().addClass('primary');
								} else {
									$ultimo = $contenido_filtrar.find('tbody tr:nth-last-child(1)').children();
								}
								$ultimo2 = $contenido_filtrar.find('tbody tr:last').children();
								$ultimo2.eq(0).find('em2').text(productos[i].descripcion);
								$ultimo.eq(0).find('img').attr('src', productos[i].imagen);
								$ultimo.eq(1).attr('data-codigo', productos[i].id_producto);
								$ultimo.eq(1).attr('data-codigo', productos[i].id_producto + '_' + productos[i].id_detalle);
								$ultimo.eq(1).text(productos[i].codigo);
								$ultimo.eq(2).find('em').text(productos[i].nombre);

								$ultimo.eq(2).find('.vencimientoView').text("Venc: " + productos[i].vencimiento);
								$ultimo.eq(2).find('.loteView').text("Lote: " + productos[i].lote);

								$ultimo.eq(2).find('.vencimiento').text("Venc: " + productos[i].vencimiento);
								$ultimo.eq(2).find('.lote').text("Lote: " + productos[i].lote);

								$ultimo.eq(2).find('.nombre_producto').text(productos[i].nombre);

								$ultimo.eq(2).find('.nombre_producto').attr('data-nombre', productos[i].id_producto + '_' + productos[i].id_detalle);
								$ultimo.eq(2).find('.vencimiento').attr('data-vencimiento', productos[i].id_producto + '_' + productos[i].id_detalle);
								$ultimo.eq(2).find('.lote').attr('data-lote', productos[i].id_producto + '_' + productos[i].id_detalle);

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

								// console.log(productos[i].cantidad_ingresos);


								////////////////////////////////
								// let str = '';
								// if (productos[i].asignacion_rol !== '') {
								// 	let asignacion_rol = productos[i].asignacion_rol.split(',');
								// 	if (asignacion_rol.includes('<?= $id_rol ?>'))
								// 		str = `*(1)${productos[i].unidad}:${productos[i].precio_actual}`;
								// } else
								// 	str = `*(1)${productos[i].unidad}:${productos[i].precio_actual}`;
								// if (typeof(productos[i].id_roles) !== 'object') {
								// 	let id_roles = productos[i].id_roles.split(',');
								// 	let Arreglo = [];
								// 	for (let i = 0; i < id_roles.length; ++i) {
								// 		let detalle = id_roles[i].split('|');
								// 		let rolA = detalle[0];
								// 		let asigA = detalle[1];
								// 		if (rolA === '<?= $id_rol ?>')
								// 			Arreglo.push(asigA);
								// 	}
								// 	if (typeof(productos[i].unidade) === 'object')
								// 		productos[i].unidade = '';
								// 	if (productos[i].unidade !== '') {
								// 		let Aux = productos[i].unidade.split('&'),
								// 			id_asignacion = productos[i].id_asignacion.split('|');
								// 		for (let i = 0; i < Aux.length; ++i) {
								// 			if (Arreglo.includes(id_asignacion[i]))
								// 				str += ` \n *(${Aux[i]}`;
								// 		}
								// 	}
								// } else {
								// 	if (typeof(productos[i].unidade) === 'object')
								// 		productos[i].unidade = '';
								// 	if (productos[i].unidade !== '') {
								// 		let Aux = productos[i].unidade.split('&');
								// 		for (let i = 0; i < Aux.length; ++i)
								// 			str += ` \n *(${Aux[i]}`;
								// 	}
								// }
								// let res = str;
								/////////////////////////////
								// var str = productos[i].unidade;
								// if (!str) {
								// 	str = '';
								// 	str = '*(1)' + productos[i].unidad + ':' + productos[i].precio_actual;
								// } else {
								// 	str = '*(1)' + productos[i].unidad + ':' + productos[i].precio_actual + '\n *(' + str;
								// 	// str = '*(' + str;
								// }
								// var res = str.replace(/&/g, "\n *(");


								//								$ultimo.eq(4).attr('data-stock', productos[i].id_producto+'_'+productos[i].id_detalle);
								//								$ultimo.eq(4).text(parseInt(productos[i].cantidad_ingresos));
								$ultimo.eq(4).attr('data-stock', productos[i].id_producto + '_' + productos[i].id_detalle);
								$ultimo.eq(4).text(parseInt(productos[i].cantidad_ingresos));


								$ultimo.eq(5).css("font-weight", "bold");
								$ultimo.eq(5).css("font-size", "0.8em");
								// $ultimo.eq(5).attr('data-valor', productos[i].id_producto);
								$ultimo.eq(5).attr('data-valor', productos[i].id_producto + '_' + productos[i].id_detalle);
								//								$ultimo.eq(5).attr('data-asignacion', productos[i].id_asignacion);
								$ultimo.eq(5).text(res);

								// $ultimo.eq(6).find(':button:first').attr('data-vender', productos[i].id_producto);
								// $ultimo.eq(6).find(':button:last').attr('data-actualizar', productos[i].id_producto);
								// $ultimo.eq(7).attr('data-cant', productos[i].id_producto);
								// $ultimo.eq(7).text(productos[i].cantidad2);
								$ultimo.eq(6).find(':button:first').attr('data-vender', productos[i].id_producto + '_' + productos[i].id_detalle);
								$ultimo.eq(6).find(':button:last').attr('data-actualizar', productos[i].id_producto + '_' + productos[i].id_detalle);
								$ultimo.eq(7).attr('data-cant', productos[i].id_producto + '_' + productos[i].id_detalle);
								$ultimo.eq(7).text(productos[i].cantidad2);

								$ultimo.eq(8).attr('data-stock2', productos[i].id_producto + '_' + productos[i].id_detalle);
								$ultimo.eq(8).text(parseInt(productos[i].cantidad_ingresos));

								$ultimo.eq(9).attr('data-contado', productos[i].id_producto + '_' + productos[i].id_detalle);
								$ultimo.eq(9).text(productos[i].descuento);
							}
						}
						if (productos.length == 1) {
							$contenido_filtrar.find('table tbody tr button').trigger('click');
						}
						$.notify({
							message: 'La operación fue ejecutada con éxito, se encontraron ' + productos.length + ' resultados.'
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
						message: 'La operación fue interrumpida por un fallo.'
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

		/*
		if ($producto.size()) {
			cantidad = $.trim($cantidad.val());
			cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
			cantidad = (cantidad < 9999999) ? cantidad + 1 : cantidad;
			$cantidad.val(cantidad).trigger('blur');
		}*/ //else {

		V_producto_simple = id_producto.split("_");
		id_producto_simple = V_producto_simple[0];

		var dt = new Date();
		var time = dt.getHours() + "" + dt.getMinutes() + "" + dt.getSeconds() + "" + dt.getMilliseconds();
		id_producto = id_producto + "_" + time;

		plantilla = '<tr class="active" data-producto="' + id_producto + '">' +
			'<td class="text-nowrap text-middle"><b>' + numero + '</b></td>' +
			'<td class="text-nowrap text-middle"><input type="text" value="' + id_producto_simple + '" name="productos[]" class="translate input-xs" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser número">' + codigo + '</td>' +
			'<td class="text-middle">' + nombre + '<br>' + lote + '<br>' + vencimiento + '';

		plantilla += '<input type="hidden" value=\'' + nombre + '\' name="nombres[]" class="form-control input-xs" data-validation="required">';
		plantilla += '<input type="hidden" value=\'' + lote + '\' name="lote[]" class="form-control input-xs" data-validation="required">';
		plantilla += '<input type="hidden" value=\'' + vencimiento + '\' name="vencimiento[]" class="form-control input-xs" data-validation="required">';

		plantilla += '</td>' +
			'<td class="text-middle"><input type="text" value="1" name="cantidades[]"  class="form-control text-right input-xs" maxlength="10" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-allowing="range[1;' + stock + ']" data-validation-error-msg="Debe ser un número positivo entre 1 y ' + stock + '" onkeyup="calcular_importe(\'' + id_producto + '\')"></td>';

		//si tiene mas de una unidad
		if (porciones.length > 2) {
			plantilla = plantilla + '<td class="text-middle"><select name="unidad[]" id="unidad[]" data-unidad="" data-xxx="true" class="form-control input-xs" style="padding-left:0;" >';
			aparte = porciones[1].split(':');
			asignation = asignaciones.split('|');

			// console.log(porciones);

			for (var ic = 1; ic < porciones.length; ic++) {
				parte = porciones[ic].split(':');
				oparte = parte[0].split(')');
				plantilla = plantilla + '<option value="' + oparte[1] + '" data-pr="' + id_producto + '" data-xyyz="' + stock + '" data-yyy="' + parte[1] + '" data-yyz="' + porci2[ic - 1] + '" data-asig="' + asignation[ic - 1] + '" >' + oparte[1] + ' (' + parte[1] + ')</option>';
			}
			plantilla = plantilla + '</select></td>' +
				'<td class="text-middle">' +

				'<input type="text" value="' + aparte[1] + '" readonly name="precios[]" class="form-control input-xs text-right class_enter" autocomplete="off" data-precio="' + aparte[1] + '"   data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(\'' + id_producto + '\')">' +
				'<input type="text" value="' + aparte[1] + '" readonly name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off" data-pre="' + aparte[1] + '"   data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(\'' + id_producto + '\')">' +

				'<input type="hidden" value="' + asignation[0] + '" readonly name="asignaciones[]" class="form-control input-xs text-right" autocomplete="off" data-asignacion="' + asignation[0] + '"   data-validation-error-msg="Debe ser un número decimal positivo">' +

				'</td>';
		} else {
			asignation = asignaciones.split('|');
			sincant = porciones[1].split(')');
			//            console.log(sincant);
			parte = sincant[1].split(':');
			plantilla = plantilla + '<td class="text-middle">' +
				'<input type="text" value="' + parte[0] + '" data-xyyz="' + stock + '" name="unidad[]" class="form-control input-xs text-right" autocomplete="off" data-unidad="' + parte[0] + '" readonly data-validation-error-msg="Debe ser un número decimal positivo"></td>' +
				'<td class="text-middle" data-xyyz="' + stock + '" >';

			<?php
			if ($_user['rol_id'] != '1') {
			?>
				if (editarxxx != 'S') {
					plantilla = plantilla + '<input type="text" readonly value="' + parte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un número decimal positivo">' +
						'<input type="text" readonly value="' + parte[1] + '" name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off"  data-pre="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un número decimal positivo">';
				} else {
					plantilla = plantilla + '<input type="text" value="' + parte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un número decimal positivo" onkeypress="SetEnter(event);" onkeyup="modificar_precio(\'' + id_producto + '\'); calcular_importe(\'' + id_producto + '\')">' +
						'<input type="text" value="' + parte[1] + '" name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off"  data-pre="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="modificar_precio(\'' + id_producto + '\'); calcular_importe(\'' + id_producto + '\')">';
				}
			<?php
			} else {
			?>
				plantilla = plantilla + '<input type="text" value="' + parte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un número decimal positivo" onkeypress="SetEnter(event);" onkeyup="modificar_precio(\'' + id_producto + '\'); calcular_importe(\'' + id_producto + '\')">' +
					'<input type="text" value="' + parte[1] + '" name="pre[]" class="form-control input-xs text-right hidden" autocomplete="off"  data-pre="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="modificar_precio(\'' + id_producto + '\'); calcular_importe(\'' + id_producto + '\')">';
			<?php
			}
			?>

			plantilla = plantilla + '<input type="hidden" value="N" readonly name="edit[]" class="form-control input-xs text-right" autocomplete="off" data-precioedit="">';
			plantilla = plantilla + '<input type="hidden" value="' + asignation[0] + '" readonly name="asignaciones[]" class="form-control input-xs text-right" autocomplete="off" data-asignacion="' + asignation[0] + '"   data-validation-error-msg="Debe ser un número decimal positivo">' +
				'</td>';
		}

		plantilla = plantilla +
			'<td class="text-nowrap text-middle text-right" data-importe="">0.00</td>' +
			'<td class="text-nowrap text-middle text-center">' +
			'<button type="button" class="btn btn-success" tabindex="-1" onclick="eliminar_producto(\'' + id_producto + '\')"><span class="glyphicon glyphicon-trash"></span></button>' +
			'</td>' +
			'</tr>';

		$ventas.append(plantilla);

		$ventas.find('[data-cantidad], [data-precio]').on('click', function() {
			$(this).select();
		});

		$ventas.find('[data-xxx]').on('change', function() {
			var v = $(this).find('option:selected').attr('data-yyy');
			var pr = $(this).find('option:selected').attr('data-pr');

			var st = $(this).find('option:selected').attr('data-xyyz');

			$(this).parent().parent().find('[data-precio]').val(v);
			$(this).parent().parent().find('[data-precio]').attr('data-precio', v);

			var z = $(this).find('option:selected').attr('data-yyz');
			var x = $.trim($('[data-stock2=' + pr + ']').text());
			var ze = Math.trunc(x / z);
			var zt = Math.trunc(st / z);
			$.trim($('[data-stock=' + pr + ']').text(ze));
			$(this).parent().parent().find('[data-cantidad]').attr('data-validation-allowing', 'range[1;' + zt + ']');
			$(this).parent().parent().find('[data-cantidad]').attr('data-validation-error-msg', 'Debe ser un número positivo entre 1 y ' + zt);
			//console.log($(this).parent().parent().find('[data-cantidad]').attr('data-validation-allowing'));
			// descontar_precio(id_producto);
			calcular_importe(id_producto);
		});

		$ventas.find('[title]').tooltip({
			container: 'body',
			trigger: 'hover'
		});

		/*
		$.validate({
			form: '#formulario',
			modules: 'basic',
			onSuccess: function() {
				guardar_nota();
			}
		});
		*/
		//}



		calcular_importe(id_producto);
	}

	function eliminar_producto(id_producto) {
		bootbox.confirm('Está seguro que desea eliminar el producto?', function(result) {
			if (result) {
				$('[data-producto=' + id_producto + ']').remove();
				renumerar_productos();
				calcular_total();
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
		var precio;

		precio = $.trim($precio.attr('data-precio'));
		precio = ($.isNumeric(precio)) ? parseFloat(precio) : 0;
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
		var $importe = $producto.find('[data-importe]');

		// Josema:: add
		var cantidad, precio, importe;

		cantidad = $.trim($cantidad.val());
		cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
		precio = $.trim($precio.val());
		precio = ($.isNumeric(precio)) ? parseFloat(precio) : 0.00;
		precioedit = $.trim($precioedit.val());

		//alert(precioedit);

		var $auxiliarP, $auxiliarC, ant_pre;
		ant_pre = $producto.find('[data-pre]').val();
		unidad = $producto.find('[data-unidad]').val();
		// console.log(unidad);

		V_producto_simple = id_producto.split("_");
		id_producto_simple = V_producto_simple[0];

		forma_pago = $('#forma_pago').val();

		var parameter = {
			'id_producto': id_producto_simple,
			'unidad': unidad,
			'cantidad': cantidad,
			'forma_pago': forma_pago
		};
		$.ajax({
			url: "?/productos/precio",
			type: "POST",
			data: parameter,
			success: function(data) {
				// console.log(data);
				// a = $.parseJSON(data);
				<?php
				//if($_user['rol'] != 'Superusuario' && precioedit!="S") {
				?>
				if (precioedit != "S") {
					$auxiliarP = data['precio_mayor'];
					$auxiliarC = data['cantidad'];
					// Asignamos el nuevo precio
					$producto.find('[data-precio]').val($auxiliarP);
				} else {
					$auxiliarP = precio;
				}
				importe = (cantidad * $auxiliarP);
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
		SwGuardar = false;

		var $ventas = $('#ventas tbody');
		var $total = $('[data-subtotal]:first');
		var $importes = $ventas.find('[data-importe]');
		var importe, total = 0;

		$importes.each(function(i) {
			importe = $.trim($(this).text());
			importe = parseFloat(importe);
			total = total + importe;
		});

		$total.text(number_format(total, 2, ',', '.'));
		$('[data-ventas]:first').val($importes.size()).trigger('blur');
		$('[data-total]:first').val(total.toFixed(2)).trigger('blur');
		set_cuotas();
	}

	function SetGuardar(event) {
		SwGuardar = true;
	}

	function guardar_nota2() {
		if (SwGuardar == true) {
			bootbox.confirm('¿Desea guardar la venta?', function(result) {
				if (result) {
					var data = $('#formulario').serialize();
					// console.log(data)
					$('#loader').fadeIn(100);

					$.ajax({
						url: '?/electronicas/guardar',
						dataType: 'json',
						type: 'post',
						contentType: 'application/x-www-form-urlencoded',
						data: data,
						success: function(result) {
							console.log(result);
							$.notify({
								message: 'La nota de remisión fue realizada satisfactoriamente.'
							}, {
								type: 'success',
								delay: 50000,
								timer: 60000,
							});
							if ($('#distribuir').val() == "N") {
								//alert(result.egreso_id+" - "+result.recibo+" - "+result.nro_recibo);

								imprimir_nota(result.egreso_id, result.recibo, result.nro_recibo);
							} else {
								window.location.reload();
							}
							// imprimir_nota(result.egreso_id);
							// window.location.reload();
						},
						error: function(error) {
							console.log(error);
							$('#loader').fadeOut(100);
							$.notify({
								message: 'Ocurrió un problema en el proceso, no se puedo guardar los datos de la nota de remisión, verifique si la se guardó parcialmente.'
							}, {
								type: 'danger'
							});
						}
					});
				} else {
					SwGuardar = false;
				}
			});
		}
	}

	function imprimir_nota(nota, recibo, nro_recibo) {
		bootbox.confirm('¿Desea imprimir la Nota de venta?', function(result) {
			if (result) {
				$.open('?/notas/imprimir_nota/' + nota, true);
				if (recibo == 'si') {
					imprimir_recibo(nro_recibo);
				} else {
					window.location.reload();
				}
			} else {
				if (recibo == 'si') {
					imprimir_recibo(nro_recibo);
				} else {
					window.location.reload();
				}
			}
		});
	}

	function imprimir_recibo(nro_recibo) {
		bootbox.confirm('¿Desea Imprimir el recibo?', function(result) {
			if (result) {
				window.open('?/cobrar/recibo_dinero/' + nro_recibo, true);
				window.location.reload();
			} else {
				window.location.reload();
			}
		});
	}

	function vender(elemento) {
		var $elemento = $(elemento),
			vender;
		vender = $elemento.attr('data-vender');
		adicionar_producto(vender);
	}

	/*function actualizar(elemento, almacen) {
		var $elemento = $(elemento),
			actualizar;
		actualizar = $elemento.attr('data-actualizar');

		$('#loader').fadeIn(100);

		$.ajax({
			type: 'post',
			dataType: 'json',
			url: '?/electronicas/actualizar',
			data: {
				id_producto: actualizar,
				almacen: almacen
			}
		}).done(function(producto) {
			if (producto) {
				var $busqueda = $('[data-busqueda="' + producto.id_producto + '"]');
				var precio = parseFloat(producto.precio).toFixed(2);
				var stock = parseInt(producto.stock);

				$busqueda.find('[data-stock]').text(stock);
				$busqueda.find('[data-valor]').text(precio);

				var $producto = $('[data-producto=' + producto.id_producto + ']');
				var $cantidad = $producto.find('[data-cantidad]');
				var $precio = $producto.find('[data-precio]');

				if ($producto.size()) {
					$cantidad.attr('data-validation-allowing', 'range[1;' + stock + ']');
					$cantidad.attr('data-validation-error-msg', 'Debe ser un número positivo entre 1 y ' + stock);
					$precio.val(precio);
					$precio.attr('data-precio', precio);
					descontar_precio(producto.id_producto);
				}

				$.notify({
					message: 'El stock y el precio del producto se actualizaron satisfactoriamente.'
				}, {
					type: 'success'
				});
			} else {
				$.notify({
					message: 'Ocurrió un problema durante el proceso, es posible que no existe un almacén principal.'
				}, {
					type: 'danger'
				});
			}
		}).fail(function() {
			$.notify({
				message: 'Ocurrió un problema durante el proceso, no se pudo actualizar el stock ni el precio del producto.'
			}, {
				type: 'danger'
			});
		}).always(function() {
			$('#loader').fadeOut(100);
		});
	}*/
	//cuentas
	//var formato = $('[data-formato]').attr('data-formato');
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

	function set_cuotas_val() {
		nro = $('#nro_cuentas').val();
		valorG = parseFloat($('[data-total]:first').val());

		valor = valorG / nro;
		for (i = 1; i <= nro; i++) {
			if (i == nro) {
				final = valorG - (valor.toFixed(1) * (i - 1));
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
		var cantidad = $('#nro_cuentas').val();
		var total = 0;

		for (i = 1; i <= x && i <= cantidad; i++) {
			importe = $('[data-cuota=' + i + ']').children('.monto_cuota').val();
			importe = parseFloat(importe);
			total = total + importe;
		}
		//console.log(total);
		valorTotal = parseFloat($('[data-total]:first').val());
		if (nro > x) {
			valor = (valorTotal - total) / (nro - x);
		} else {
			valor = 0;
		}

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
			$('[data-total-pagos]:first').parent('div').children('#monto_plan_pagos').attr("data-validation-error-msg", "La suma de las cuotas es diferente al costo total « " + total.toFixed(1) + "0" + " / " + valor.toFixed(1) + "0" + " »");
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


	function sidenav() {
		let contenedor = document.getElementById('ContenedorF');
		if (contenedor.children[0].classList.contains('col-md-6')) {
			contenedor.children[0].classList.remove('col-md-6');
			contenedor.children[0].classList.add('col-md-12');
			contenedor.children[1].classList.add('hidden');
		} else {
			contenedor.children[0].classList.remove('col-md-12');
			contenedor.children[0].classList.add('col-md-6');
			contenedor.children[1].classList.remove('hidden');
		}
	}

	function mostrar_datos(id_cliente) {
		var parameter = {
			'id_cliente': id_cliente
		};

		$.ajax({
			url: '?/clientes/historial',
			type: 'POST',
			data: parameter,
			dataType: 'json',
			success: function(data) {
				$('#compras_cliente').html(data.basico);
				if (data.avanzado) {
					$('#para_deudas').removeClass('hidden');
					$('#historial_deudas').html(data.avanzado);
				} else {
					$('#para_deudas').addClass('hidden');
				}
			}
		});
	}

	function Ftipo_pago() {
		var tipo = $('#tipo_pago').val();
		var n_f = $('#nro_factura').val();
		console.log(n_f);
		if (tipo == 'EFECTIVO') {
			$('#nro_pago').css({
				'display': 'none'
			});
			$('#nro_pago').val(n_f);
		} else {
			$('#nro_pago').css({
				'display': 'block'
			});
			$('#nro_pago').attr('placeholder', 'Ingrese el Nro. de transaccion');
			$('#nro_pago').val('');
		}
	}
	//////////////////////////////////////////////////////////////////////
	function seleccionar_vendedor(id_vendedor) {
		$('#empleado option')
			.removeAttr('selected')
			.filter('[value="' + id_vendedor + '"]')
			.attr('selected', true).change();
		$('#empleado').val(id_vendedor);
	}
	/////////////////////////////////////////////////////////////////////
	$("#forma_pago").change(function() {
		if ($('#forma_pago').val() == 2) {
			$('#para_pagos').addClass('hidden');
			//   $('#cambiar_plan').removeClass('col-md-6 col-sm-6 col-xs-6');
			//   $('#cambiar_plan').addClass('col-md-12 col-sm-12 col-xs-12');
		} else {
			$('#para_pagos').removeClass('hidden');
			//   $('#cambiar_plan').removeClass('col-md-12 col-sm-12 col-xs-12');
			//   $('#cambiar_plan').addClass('col-md-6 col-sm-6 col-xs-6');
		}
	});
</script>
<?php require_once show_template('footer-empty'); ?>