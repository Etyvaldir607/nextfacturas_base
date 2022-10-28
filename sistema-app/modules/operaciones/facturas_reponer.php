<?php
    // Obtiene el id del ingreso a reponer
    $id_ingreso = (sizeof($params) > 0) ? $params[0] : 0;
    $id_detalle = (sizeof($params) > 0) ? $params[1] : 0;

    // Obtiene el ingreso
	$ingreso = $db->select('i.*, a.almacen')
					->from('inv_ingresos i')
					->join('inv_almacenes a', 'a.id_almacen = i.almacen_id', 'left')
					->where('i.id_ingreso', $id_ingreso)
					->fetch_first();

    // Obtiene los detalles del ingreso
    $detalles = $db->from('inv_ingresos_detalles')->where('id_detalle', $id_detalle)->fetch();

    // Obtenemos el egreso para capturar los datos
    $egreso = $db->from('inv_egresos')->where('id_egreso', $ingreso['egreso_id'])->fetch_first();


    // Obtenemos los egresos que sean reposicion del ingreso
    // $egresos = $db->from('inv_egresos')->where('ingreso_id', $id_ingreso)->fecth();
    // echo json_encode($detalles); die();

    // Obtiene la moneda oficial
	$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
	// echo json_encode($moneda); die();
	$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';
	
    $id_user=$_SESSION[user]['id_user'];
    $id_rol=$db->query("SELECT rol_id FROM sys_users WHERE id_user='{$id_user}'")->fetch_first()['rol_id'];
?>

<?php require_once show_template('header-advanced'); ?>

<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Reposicion de productos devueltos</strong>
	</h3>
</div>
<div class="panel-body">
    <!-- <div class="row">
        <div class="col-sm-8 hidden-xs">
            <div class="text-label">Para volver al detalle hacer clic en el siguiente botón: </div>
        </div>
        <div class="col-xs-12 col-sm-4 text-right">
            <a href="?/ingreso/notas_listar" class="btn btn-primary"><span class="glyphicon glyphicon-arrow-left"></span><span class="hidden-xs"> Listar</span></a>
        </div>
    </div>
    <hr> -->

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-list"></span>
						<strong>Datos de la reposición</strong>
					</h3>
				</div>
				<form method="post" action="?/operaciones/facturas_reponer_guardar" class="form-horizontal" id="send-form">
					<input type="text" class="hidden" name="ingreso_id" value="<?= $ingreso['id_ingreso'] ?>">
					<input type="text" class="hidden" name="egreso_id" value="<?= $egreso['id_egreso'] ?>">
					<input type="text" class="hidden" name="cliente_id" value="<?= $egreso['cliente_id'] ?>">
					<input type="text" class="hidden" name="detalle_ingreso_id" value="<?= $id_detalle ?>">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="almacen" class="col-sm-4 control-label">Almacen:</label>
                            <div class="col-sm-8">
                                <input type="text" value="<?= $ingreso['almacen']?>" name="almacen" id="almacen" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./& " data-validation-length="max100" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nombre_cliente" class="col-sm-4 control-label">Señor(es):</label>
                            <div class="col-sm-8">
                                <input type="text" value="<?= $egreso['nombre_cliente']?>" name="nombre_cliente" id="nombre_cliente" class="form-control text-uppercase" autocomplete="off" data-validation="required letternumber length" data-validation-allowing="-+./& " data-validation-length="max100" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nit_ci" class="col-sm-4 control-label">NIT / CI:</label>
                            <div class="col-sm-8">
                                <input type="text" value="<?= $egreso['nit_ci'] ?>" name="nit_ci" id="nit_ci" class="form-control text-uppercase" autocomplete="off" data-validation="required number" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tipo" class="col-sm-4 control-label">Reposicion por:</label>
                            <div class="col-sm-8">
                                <input type="text" value="<?= $ingreso['tipo'] ?>" name="tipo" id="tipo" class="form-control text-uppercase" autocomplete="off" data-validation="required" readonly>
                            </div>
						</div>
						
						<div class="form-group">
                            <label for="tipo" class="col-sm-4 control-label">Descripción:</label>
                            <div class="col-sm-8">
                                <textarea name="descripcion" id="descr" rows="2" class="form-control"></textarea>
                            </div>
                        </div>

                        <hr>

                        <div class="table-responsive margin-none">
                            <table id="ventas" class="table table-bordered table-condensed table-striped table-hover table-xs text-sm margin-none">
                                <thead>
                                    <tr class="active">
										<th class="text-nowrap text-center">#</th>
										<th class="text-nowrap text-center">Código</th>
										<th class="text-nowrap text-center">Producto</th>
										<th class="text-nowrap text-center">Cantidad</th>
										<th class="text-nowrap text-center">Unidad</th>
										<th class="text-nowrap text-center">Precio</th>
										<!-- <th class="text-nowrap text-center">Descuento</th> -->
										<th class="text-nowrap text-center">Importe</th>
										<th class="text-nowrap text-center">Acciones</th>
									</tr>
                                </thead>
                                <tfoot>
                                <tr class="active">
                                    <th class="text-nowrap text-right" colspan="6">Importe total <?= escape($moneda); ?></th>
                                    <th class="text-nowrap text-right" data-subtotal="">0.00</th>
                                    <th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
                                </tr>
                                </tfoot>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <div id="alerta"></div>
                        <div class="form-group">
							<div class="col-xs-12">
								<input type="text" name="almacen_id" value="<?= $egreso['almacen_id']; ?>" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="El almacén no esta definido">
								<input type="text" name="nro_registros" value="0" class="translate" tabindex="-1" data-ventas="" data-validation="required number" data-validation-allowing="range[1;20]" data-validation-error-msg="El número de productos a vender debe ser mayor a cero y menor a 20">
								<input type="text" name="monto_total" value="0" class="translate" tabindex="-1" data-total="" data-validation="required number" data-validation-allowing="range[0.01;1000000.00],float" data-validation-error-msg="El monto total de la venta debe ser mayor a cero y menor a 1000000.00">
							</div>
                        </div>
                        
                        <div class="form-group">
                            <div class="col-xs-12 text-right">
                                <button type="button" class="btn btn-primary" id="guardar" onclick="enviar_form()">
                                    <span class="glyphicon glyphicon-floppy-disk"></span>
                                    <span>Guardar</span>
                                </button>
                                <button type="reset" class="btn btn-default">
                                    <span class="glyphicon glyphicon-refresh"></span>
                                    <span>Restablecer</span>
                                </button>
                            </div>
                        </div>
                        
                    </div>
                </form>
            </div>

            <div class="panel panel-default" data-servidor="<?= ip_local . name_project . '/factura.php'; ?>">
				<div class="panel-heading">
					<h3 class="panel-title">
						<span class="glyphicon glyphicon-cog"></span>
						<strong>Datos generales</strong>
					</h3>
				</div>
				<div class="panel-body">
					<ul class="list-group">
						<li class="list-group-item">
							<i class="glyphicon glyphicon-home"></i>
							<strong>Casa Matriz: </strong>
							<span><?= escape($_institution['nombre']); ?></span>
						</li>
						<li class="list-group-item">
							<i class="glyphicon glyphicon-qrcode"></i>
							<strong>NIT: </strong>
							<span><?= escape($_institution['nit']); ?></span>
						</li>
						<li class="list-group-item">
							<i class="glyphicon glyphicon-user"></i>
							<strong>Empleado: </strong>
							<span><?= escape($_user['nombres'] . ' ' . $_user['paterno'] . ' ' . $_user['materno']); ?></span>
						</li>
					</ul>
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
                    <table id="productos" class="table table-bordered table-condensed table-striped table-hover table-xs text-sm">
                        <thead>
                            <tr class="active">
                                <th class="text-nowrap">Imagen</th>
                                <th class="text-nowrap">Código</th>
                                <th class="text-nowrap">Producto</th>
                                <th class="text-nowrap">Categoria</th>
                                <th class="text-nowrap">Stock</th>
                                <th class="text-nowrap">Precio</th>
                                <th class="text-nowrap">Acciones</th>
                                <th class="text-nowrap hidden"></th>
                                <th class="text-nowrap hidden"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $cant_val = 0;
                                 foreach ($detalles as $key => $value) { 
                                $cant_val = $cant_val + $value['cantidad']; ?>
                                <?php
                                $productos = $db->query("SELECT p.id_producto,p.asignacion_rol, p.descuento ,p.promocion, z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2,
                                                                    p.descripcion,p.imagen,p.codigo,p.nombre_factura as nombre,p.nombre_factura,p.cantidad_minima,p.precio_actual,
                                                                    IFNULL(e.cantidad_ingresos, 0) AS cantidad_ingresos, IFNULL(s.cantidad_egresos, 0) AS cantidad_egresos,
                                                                    u.unidad, u.sigla, c.categoria, e.vencimiento, e.lote,e.id_detalle, '' as id_detalle_productos, (e.cantidad_ingresos - IFNULL(s.cantidad_egresos, 0)) as stock_actual
                                                            FROM inv_productos p
                                                                LEFT JOIN (SELECT d.producto_id, SUM(d.cantidad) AS cantidad_ingresos, d.vencimiento, d.lote, d.id_detalle
                                                                            FROM inv_ingresos_detalles d
                                                                            LEFT JOIN inv_ingresos i ON i.id_ingreso = d.ingreso_id
                                                                            WHERE i.almacen_id = '". $ingreso['almacen_id']. "'
                                                                            GROUP BY d.producto_id, d.vencimiento, d.lote
                                                                        ) AS e ON e.producto_id = p.id_producto
                                                                LEFT JOIN (SELECT d.producto_id, SUM(d.cantidad) AS cantidad_egresos, d.lote
                                                                            FROM inv_egresos_detalles d
                                                                            LEFT JOIN inv_egresos e ON e.id_egreso = d.egreso_id
                                                                            WHERE e.almacen_id = '". $ingreso['almacen_id']. "'
                                                                            GROUP BY d.producto_id,d.vencimiento, d.lote
                                                                        ) AS s ON s.producto_id = p.id_producto AND s.lote = e.lote
                                                                LEFT JOIN inv_unidades u ON u.id_unidad = p.unidad_id
                                                                LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id
                                                                LEFT JOIN (SELECT w.producto_id,
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
                                                            WHERE p.id_producto = '" . $value['producto_id'] . "'
                                                            AND e.vencimiento >= '" . date('Y-m-d'). "'")->fetch();
                                // echo $db->last_query();
                                foreach ($productos as $nro => $producto) {
                                    // $otro_precio=$db->query("SELECT *
                                    //             FROM inv_asignaciones a
                                    //             LEFT JOIN inv_unidades b ON a.unidad_id=b.id_unidad
                                    //             LEFT JOIN inv_asignaciones_por_roles AS apr ON apr.asignacion_id=a.id_asignacion
                                    //             WHERE a.producto_id='{$producto['id_producto']}' AND rol_id='{$id_rol}'")->fetch();

                                    $lote = loteProducto($db, $producto['id_producto'], $ingreso['almacen_id']);
                                    $otro_precio = $db->select('*')->from('inv_asignaciones a')->join('inv_unidades b','a.unidad_id=b.id_unidad')->where('a.producto_id',$producto['id_producto'])->fetch();

                                    if ($producto['stock_actual'] > 0) {
                                ?>
                                    <tr data-busqueda="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>">
										<td class="text-nowrap text-middle text-center width-none">
											<img src="<?= ($producto['imagen'] == '') ? imgs . '/image.jpg' : files . '/productos/' . $producto['imagen']; ?>" class="img-rounded cursor-pointer" data-toggle="modal" data-target="#modal_mostrar" data-modal-size="modal-md" data-modal-title="Imagen" width="75" height="75">
										</td>
										<td class="text-nowrap text-middle" data-codigo="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>" data-codigo2=""><?= escape($producto['codigo']); ?></td>
										<td class="text-middle">
											<em><?= escape($producto['nombre']); ?></em>
											<br>
											<span class="vencimientoView" data-vencimientoview="">Venc: <?= escape($producto['vencimiento']); ?></span>
											<br>
											<span class="loteView" data-loteview="">Lote: <?= escape($producto['lote']); ?></span>
											<span class="vencimiento hidden" data-vencimiento="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>">Venc: <?= escape($producto['vencimiento']); ?></span>
											<span class="lote hidden" data-lote="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>">Lote: <?= escape($producto['lote']); ?></span>
											<span class="nombre_producto hidden" data-nombre="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['nombre']); ?></span>
										</td>
										<td class="text-nowrap text-middle"><?= escape($producto['categoria']); ?></td>
										<td class="text-nowrap text-middle text-right" data-stock="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['cantidad_ingresos'] - $producto['cantidad_egresos']); ?></td>
										<td class="text-middle text-right" data-valor="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>" data-asignacion="" style="font-weight: bold; font-size: 0.8em;">
										<!-- *(1)CAJA:30.00 
										*(10)BOLSA:1000.00 -->
											<?php
												if($producto['asignacion_rol']!=''):
													$asignacion_rol=explode(',',$producto['asignacion_rol']);
													if(in_array($id_rol,$asignacion_rol)):
														echo '*(1)' .escape($producto['unidad']).': <b>'.escape($producto['precio_actual']).'</b>';
													endif;
												else:
													echo '*(1)'.escape($producto['unidad']).': <b>'.escape($producto['precio_actual']).'</b>';
												endif;
												foreach ($otro_precio as $otro){
											?>
												<br />*(<?= escape($producto['cantidad2']) . ')' .escape($otro['unidad'] . ': '); ?><b><?= escape($otro['otro_precio']); ?></b>
											<?php } ?>
										</td>
										<td class="text-nowrap text-middle text-center width-none">
											<button type="button" class="btn btn-warning" data-vender="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>" >
											<span class="glyphicon glyphicon-shopping-cart"></span>
											<!--Vender-->
											</button>
											<button type="button" class="btn btn-default" data-actualizar="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>" onclick="actualizar(this, <?= $ingreso['almacen_id'] ?>);calcular_descuento()">
											<span class="glyphicon glyphicon-refresh"></span>
											<!--Actualizar-->
											</button>
										</td>
										<td class="hidden" data-cant="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['cantidad2']); ?></td>
										<td class="hidden" data-stock2="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['cantidad_ingresos'] - $producto['cantidad_egresos']); ?></td>
									</tr>
                                
                                    <?php } 
                                }?>
                            <?php } ?>
                            <input type="text" class="hidden" id="cantidadtotal" value="<?= $cant_val; ?>">
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/selectize.min.js"></script>
<script src="<?= js; ?>/bootstrap-notify.min.js"></script>
<script>
    $(function () {
        table = $('#productos').DataTable({
            info: false,
            lengthMenu: [[25, 50, 100, 500, -1], [25, 50, 100, 500, 'Todos']],
            order: []
        });

        calcular_total();
        calcular_productos();
        var table;
        var $cliente = $('#cliente');
        var $nit_ci = $('#nit_ci');
        var $nombre_cliente = $('#nombre_cliente');

        $('[data-vender]').on('click', function () {
            adicionar_producto($.trim($(this).attr('data-vender')));
        });

        $.validate({
            modules: 'basic'
        });

    });

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
		// cantidad2 = cantidad2;
		z = 1;
		var porci2 = cantidad2.split('*');
		//console.log(porci2);
		if ($producto.size()) {
			cantidad = $.trim($cantidad.val());
			cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
			cantidad = (cantidad < 9999999) ? cantidad + 1 : cantidad;
			$cantidad.val(cantidad).trigger('blur');
		} else {

		V_producto_simple=id_producto.split("_");
		id_producto_simple=V_producto_simple[0];

		plantilla = '<tr class="active" data-producto="' + id_producto + '">' +
			'<td class="text-nowrap text-middle"><b>' + numero + '</b></td>' +
			'<td class="text-nowrap text-middle"><input type="text" value="' + id_producto_simple + '" name="productos[]" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser número">' + codigo + '</td>' +
			'<td class="text-middle">'+nombre+'<br>'+lote+'<br>'+vencimiento+'';

		plantilla += '<input type="hidden" value=\'' + nombre + '\' name="nombres[]" class="form-control" data-validation="required">';
		plantilla += '<input type="hidden" value=\'' + lote + '\' name="lotes[]" class="form-control" data-validation="required">';
		plantilla += '<input type="hidden" value=\'' + vencimiento + '\' name="vencimiento[]" class="form-control" data-validation="required">';

		plantilla += '</td>' +
			'<td class="text-middle"><input type="text" value="1" name="cantidades[]"  class="form-control input-xs text-right" maxlength="10" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-allowing="range[1;' + stock + ']" data-validation-error-msg="Debe ser un número positivo entre 1 y ' + stock + '" onkeyup="calcular_importe(\'' + id_producto + '\')"></td>';

		//si tiene mas de una unidad
		if (porciones.length > 2) {
			plantilla = plantilla + '<td class="text-middle"><select name="unidad[]" id="unidad[]" data-xxx="true" class="form-control input-xs" style="padding-left:0;">';
			aparte = porciones[1].split(':');
			asignation = asignaciones.split('|');

			// console.log(porciones);

			for (var ic = 1; ic < porciones.length; ic++) {
				parte = porciones[ic].split(':');
				oparte = parte[0].split(')');

				            //    console.log(parte);
				plantilla = plantilla + '<option value="' + oparte[1] + '" data-pr="'+ id_producto +'" data-xyyz="' + stock + '" data-yyy="' + parte[1] + '" data-yyz="' + porci2[ic - 1] + '" data-asig="' + asignation[ic - 1] + '" >' + oparte[1] + ' ('+parte[1]+')</option>';
			}
			plantilla = plantilla + '</select></td>' +
				'<td class="text-middle">' +

				'<input type="text" value="' + aparte[1] + '" readonly name="precios[]" class="form-control input-xs text-right" autocomplete="off" data-precio="' + aparte[1] + '"   data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(\'' + id_producto + '\')">' +
				
				'<input type="hidden" value="' + asignation[0] + '" readonly name="asignaciones[]" class="form-control input-xs text-right" autocomplete="off" data-asignacion="' + asignation[0] + '"   data-validation-error-msg="Debe ser un número decimal positivo">' +
				
				'</td>';
		} else {
			asignation=asignaciones.split('|');
			sincant = porciones[1].split(')');
			parte = sincant[1].split(':');
			plantilla = plantilla + '<td class="text-middle">' +
				'<input type="text" value="' + parte[0] + '" data-xyyz="' + stock + '" name="unidad[]" class="form-control input-xs text-right" autocomplete="off" data-unidad="' + parte[0] + '" readonly data-validation-error-msg="Debe ser un número decimal positivo"></td>' +
				'<td data-xyyz="' + stock + '" >' +
				
				'<input type="text" readonly value="' + parte[1] + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off"  data-precio="' + parte[1] + '" data-cant2="1"   data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(\'' + id_producto + '\')">' +
				
				'<input type="hidden" value="' + asignation[0] + '" readonly name="asignaciones[]" class="form-control input-xs text-right" autocomplete="off" data-asignacion="' + asignation[0] + '"   data-validation-error-msg="Debe ser un número decimal positivo">' +
				'</td>';
		}

		plantilla = plantilla + '' +
			'<td class="text-nowrap text-middle text-right" data-importe="">0.00</td>' +
			'<td class="text-nowrap text-middle text-center">' +
			'<button type="button" class="btn btn-success" tabindex="-1" onclick="eliminar_producto(\'' + id_producto + '\')"><span class="glyphicon glyphicon-trash"></span></button>' +
			'</td>' +
			'</tr>';

            // <td><input type="text" value="0" name="descuentos[]" class="form-control input-xs text-right" maxlength="10" autocomplete="off" data-descuento="0" data-validation="required number" data-validation-allowing="float,range[-100.00;100.00],negative" data-validation-error-msg="Debe ser un número entre -100.00 y 100.00" onkeyup="descontar_precio(\'' + id_producto + '\')">

		$ventas.append(plantilla);

		$ventas.find('[data-cantidad], [data-precio], [data-descuento]').on('click', function() {
			$(this).select();
		});

		$ventas.find('[data-xxx]').on('change', function() {
            var v = $(this).find('option:selected').attr('data-yyy');
            var pr = $(this).find('option:selected').attr('data-pr');
			// var az = $(this).find('option:selected').attr('data-asig');
        //    console.log(az);
		//	var az = $(this).find('option:selected').attr('data-asig');
			var st = $(this).find('option:selected').attr('data-xyyz');
		//	//	$(this).parent().parent().find('[data-asignacion]').val(az);
		//	$(this).parent().parent().find('[data-asignacion]').attr(az);

			$(this).parent().parent().find('[data-precio]').val(v);
			$(this).parent().parent().find('[data-precio]').attr('data-precio',v);

			var z = $(this).find('option:selected').attr('data-yyz');
			var x = $.trim($('[data-stock2=' + id_producto + ']').text());
			var ze = Math.trunc(x / z);
			var zt = Math.trunc(st / z);
			$.trim($('[data-stock=' + pr + ']').text(ze));
			$(this).parent().parent().find('[data-cantidad]').attr('data-validation-allowing', 'range[1;' + zt + ']');
			$(this).parent().parent().find('[data-cantidad]').attr('data-validation-error-msg', 'Debe ser un número positivo entre 1 y ' + zt);
			//console.log($(this).parent().parent().find('[data-cantidad]').attr('data-validation-allowing'));
			// descontar_precio(id_producto);
			calcular_importe(id_producto);
            calcular_productos();

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
		}



		calcular_importe(id_producto);
        calcular_productos();
	}

	function eliminar_producto(id_producto) {
		bootbox.confirm('Está seguro que desea eliminar el producto?', function(result) {
			if (result) {
				$('[data-producto=' + id_producto + ']').remove();
				renumerar_productos();
				calcular_total();
				calcular_descuento();
                calcular_productos();
			}
		});
	}

	function renumerar_productos() {
		var $ventas = $('#ventas tbody');
		var $productos = $ventas.find('[data-producto]');
		$productos.each(function(i) {
			$(this).find('td:first').text(i + 1);
		});
        calcular_productos();
	}

    function calcular_total() {
		var $ventas = $('#ventas tbody');
		var $total = $('[data-subtotal]:first');
		var $importes = $ventas.find('[data-importe]');
		var importe, total = 0;

		$importes.each(function(i) {
			importe = $.trim($(this).text());
			importe = parseFloat(importe);
			total = total + importe;
		});

		$total.text(total.toFixed(2));
		$('[data-ventas]:first').val($importes.size()).trigger('blur');
		$('[data-total]:first').val(total.toFixed(2)).trigger('blur');
		// calcular_descuento_total();
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
		//	$precio.val(precio.toFixed(2));

		calcular_importe(id_producto);
	}

	function calcular_importe(id_producto) {
		// console.log(id_producto);
		var $producto = $('[data-producto=' + id_producto + ']');
		var $cantidad = $producto.find('[data-cantidad]');
		var $precio = $producto.find('[data-precio]');
		var $descuento = $producto.find('[data-descuento]');
        var $importe = $producto.find('[data-importe]');

		var cantidad, precio, importe, fijo, descuento;

		fijo = $descuento.attr('data-descuento');
		fijo = ($.isNumeric(fijo)) ? parseFloat(fijo) : 0;
		cantidad = $.trim($cantidad.val());
		cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
		precio = $.trim($precio.val());
		precio = ($.isNumeric(precio)) ? parseFloat(precio) : 0.00;
		descuento = $.trim($descuento.val());
		descuento = ($.isNumeric(descuento)) ? parseFloat(descuento) : 0;
		importe = (cantidad * precio) - descuento;
		importe = importe.toFixed(2);
		$importe.text(importe);

		calcular_total();
        calcular_productos();
		// setTimeout(() => {
		// 	calcular_descuentoF()
		// }, 250);
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
		$total.text(total.toFixed(2));
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

    // Calcular cantidad de productos
    function calcular_productos() {

        var t_can = $("#cantidadtotal").val();

        var $ventas = $('#ventas tbody');
        var $cantidades = $ventas.find('[data-cantidad]');

        var importe, total = 0;

        $cantidades.each(function (i) {
            importe = $.trim($(this).val());
            importe = parseInt(importe);
            total = total + importe;
        });

        if (total == t_can) {
            $('#alerta').html('<div class="alert alert-success" role="alert"><strong> La cantidad general permitida es de: ' + t_can + '</strong></div>');
            $('#guardar').removeClass('disabled');
        } else {
            $('#alerta').html('<div class="alert alert-danger" role="alert"><strong> La cantidad general debe ser igual a: ' + t_can + '</strong></div>');
            $('#guardar').addClass('disabled');
        }

	}
	
	function enviar_form(){
		if ($('#guardar').hasClass('disabled')) {
			bootbox.confirm('Por favor seleccione la cantidad exacta de los productos para la reposición!!!', function(result) {
				// if (result) {

				// }
			});
		} else {
			$('#send-form').submit();
		}
	}
</script>
<?php require_once show_template('footer-advanced'); ?>