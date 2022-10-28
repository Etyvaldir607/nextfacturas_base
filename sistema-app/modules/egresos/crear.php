<?php

// Obtiene el id_almacen
$id_almacen = (isset($params[0])) ? $params[0] : 0;

// Obtiene el almacen principal
$almacen = $db->from('inv_almacenes')->where('id_almacen', $id_almacen)->fetch_first();
$visitadores = $db->query(" SELECT e.id_empleado, e.nombres, e.paterno, e.materno
                            FROM sys_empleados as e
                            LEFT JOIN sys_users as u ON u.persona_id = e.id_empleado
                            LEFT JOIN sys_roles as r ON r.id_rol = u.rol_id
                            WHERE r.rol = 'Visitador'
                          ")->fetch();
// Verifica si existe el almacen
if (!$almacen) {
	// Error 404
	require_once not_found();
	exit;
}

// Obtiene los productos
$productos = $db->query("   select  p.id_producto, p.codigo, p.nombre_factura as nombre, p.cantidad_minima, p.precio_actual,
                                    e.costo, ifnull(e.cantidad_ingresos, 0) as cantidad_ingresos, 0 as cantidad_egresos, u.unidad, u.sigla, c.categoria,
                                    e.vencimiento, e.lote,e.id_detalle, '' as id_detalle_productos, e.factura_v
                            from inv_productos p 
                            left join (SELECT d.producto_id, SUM(d.lote_cantidad) AS cantidad_ingresos, d.vencimiento, d.lote, d.id_detalle, d.costo, d.factura_v
                                        FROM inv_ingresos_detalles d
                                        LEFT JOIN inv_ingresos i ON i.id_ingreso = d.ingreso_id
                                        WHERE i.almacen_id = '$id_almacen'
                                        GROUP BY d.producto_id, d.vencimiento, d.lote, d.costo) as e on e.producto_id = p.id_producto
                            left join inv_unidades u on u.id_unidad = p.unidad_id 
                            left join inv_categorias c on c.id_categoria = p.categoria_id
                            WHERE   (cantidad_ingresos > 0 OR cantidad_ingresos IS NOT NULL) AND p.visible='s' 
                        ")->fetch();

// Obtiene la moneda oficial
$moneda = $db->from('inv_monedas')->where('oficial', 'S')->fetch_first();
$moneda = ($moneda) ? '(' . $moneda['sigla'] . ')' : '';

// Obtiene los permisos
$permisos = explode(',', permits);


// Almacena los permisos en variables
$permiso_listar = in_array('listar', $permisos);

?>
<?php require_once show_template('header-empty'); ?>
<style>
	.table-xs tbody {
		font-size: 12px;
	}

	.input-xs {
		height: 22px;
		padding: 1px 5px;
		font-size: 12px;
		line-height: 1.5;
		border-radius: 3px;
	}

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
</style>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					<span class="glyphicon glyphicon-option-vertical"></span>
					<strong>Preventas</strong>
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
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					<span class="glyphicon glyphicon-list"></span>
					<strong>Datos del egreso</strong>
				</h3>
			</div>
			<div class="panel-body">
				<form method="post" action="?/egresos/guardar" id="formulario" class="form-horizontal">
					<div class="form-group">
						<label class="col-sm-4 control-label">Almacén:</label>
						<div class="col-sm-8">
							<p class="form-control-static"><?= escape($almacen['almacen'] . (($almacen['principal'] == 'S') ? ' (principal)' : (($almacen['especial']=='si') ? ' (especial)' : ''))); ?></p>
						</div>
					</div>
					
					<div class="form-group">
						<label for="tipo" class="col-sm-4 control-label">Tipo de egreso:</label>
						<div class="col-sm-8">
							<select name="tipo" id="tipo" class="form-control" data-validation="required" onchange="ca(this,1); listar_visitadores(this);"> <!--ca(this,1)--> 
							    <option value="">Seleccionar</option>
							    <!-- <option value="Traspaso">Egreso como traspaso</option> -->
    							
							    <?php if($almacen['especial']=='si'){ ?>
    								<option value="Egreso especial" selected>Egreso especial</option>
							    <? }else{?>
    								
								<? } ?>
								<option value="Baja" selected>Egreso como baja</option>
							</select>
						</div>
					</div>
					<div class="form-group" id="visitadores_select" style="display:none"> <!--style="display:none"-->
						<label for="visitadores" class="col-sm-4 control-label">Visitador:</label>
						<div class="col-sm-8">
							<select name="visitador" id="visitador" class="form-control text-uppercase" data-validation-optional="true">
								<option value="">Buscar</option>
								<?php foreach ($visitadores as $elemento) { ?>
                                    <option value="<?= escape($elemento['id_empleado']); ?>"><?= escape($elemento['paterno']).' '.escape($elemento['materno']).' '.escape($elemento['nombres']); ?></option>
                                <?php } ?>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label for="descripcion" class="col-sm-4 control-label">Descripción:</label>
						<div class="col-sm-8">
							<textarea name="descripcion" id="descripcion" class="form-control" autocomplete="off" data-validation="letternumber" data-validation-allowing="+-/.,:;#º()\n " data-validation-optional="true"></textarea>
						</div>
					</div>
					<div class="table-responsive margin-none">
						<table id="ventas" class="table table-bordered table-condensed table-striped table-hover table-xs margin-none">
							<thead>
								<tr class="active">
									<th class="text-nowrap">#</th>
									<th class="text-nowrap">Código</th>
									<th class="text-nowrap">Nombre</th>
									<th class="text-nowrap">Cantidad</th>
									<th class="text-nowrap">Costo</th>
									<th class="text-nowrap">Importe</th>
									<th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
								</tr>
							</thead>
							<tfoot>
								<tr class="active">
									<th class="text-nowrap text-right" colspan="5">Importe total <?= escape($moneda); ?></th>
									<th class="text-nowrap text-right">
										<span data-subtotal="">0.00</span>
										<span data-subtotalsi="" class="hidden">0.00</span>
									</th>
									<th class="text-nowrap text-center"><span class="glyphicon glyphicon-trash"></span></th>
								</tr>
							</tfoot>
							<tbody></tbody>
						</table>
					</div>
					<div class="form-group">
						<div class="col-xs-12">
							<input type="text" name="almacen_id" value="<?= $almacen['id_almacen']; ?>" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="El almacén no esta definido">
							<input type="text" name="nro_registros" value="0" class="translate" tabindex="-1" data-ventas="" data-validation="required number" data-validation-allowing="range[1;1000]" data-validation-error-msg="El número de productos a vender debe ser mayor a cero y menor a 1000">
							<input type="text" name="monto_total" value="0" class="translate" tabindex="-1" data-total="" data-validation="required number" data-validation-allowing="range[0.01;10000000.00],float" data-validation-error-msg="El monto total de la venta debe ser mayor a cero y menor a 1000000.00">
							<input type="text" name="monto_total_si" value="0" class="translate hidden" tabindex="-1" data-totalsi="" data-validation="required number" data-validation-allowing="range[0.01;10000000.00],float" data-validation-error-msg="El monto total de la venta debe ser mayor a cero y menor a 10.000.000.00">
						</div>
					</div>
					<div class="form-group">
						<div class="col-xs-12 text-right">
							<button type="submit" class="btn btn-primary">
								<span class="glyphicon glyphicon-floppy-disk"></span>
								<span>Guardar</span>
							</button>
							<button type="reset" class="btn btn-default">
								<span class="glyphicon glyphicon-refresh"></span>
								<span>Restablecer</span>
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					<span class="glyphicon glyphicon-cog"></span>
					<strong>Datos generales</strong>
				</h3>
			</div>
			<div class="panel-body">
				<ul class="list-group">
					<li class="list-group-item">
						<span class="glyphicon glyphicon-home"></span>
						<strong>Casa Matriz: </strong>
						<span><?= escape($_institution['nombre']); ?></span>
					</li>
					<li class="list-group-item">
						<span class="glyphicon glyphicon-qrcode"></span>
						<strong>NIT: </strong>
						<span><?= escape($_institution['nit']); ?></span>
					</li>
					<li class="list-group-item">
						<span class="glyphicon glyphicon-user"></span>
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
				<?php if ($permiso_listar) { ?>
					<div class="row">
						<div class="col-xs-12 text-right">
							<a href="?/egresos/listar" class="btn btn-primary">
								<span class="glyphicon glyphicon-list-alt"></span>
								<span>Lista de egresos</span></a>
						</div>
					</div>
					<hr>
				<?php } ?>
				<?php if ($productos) { 
				    
				?>
					<table id="productos" class="table table-bordered table-condensed table-striped table-hover table-xs">
						<thead>
							<tr class="active">
								<th class="text-nowrap">Código</th>
								<th class="text-nowrap">Nombre</th>
								<th class="text-nowrap">Categoría</th>
								<th class="text-nowrap">Stock</th>
								<th class="text-nowrap">Costo</th>
								<th class="text-nowrap"><span class="glyphicon glyphicon-cog"></span></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($productos as $nro => $producto) { 
							    if ( ($producto['cantidad_ingresos'] - $producto['cantidad_egresos']) > 0) { 
							    $costo = $db->query("SELECT a.* FROM inv_ingresos_detalles a LEFT JOIN inv_ingresos b ON b.id_ingreso = a.ingreso_id WHERE a.producto_id = ".$producto['id_producto']." and b.almacen_id = '$id_almacen' ORDER BY a.ingreso_id ")->fetch_first();
							?>
								<tr>
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
									<td class="text-nowrap"><?= escape($producto['categoria']); ?></td>
                                    <td class="text-nowrap text-middle text-right" data-stock="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= escape($producto['cantidad_ingresos'] - $producto['cantidad_egresos']); ?></td>
									<td class="text-nowrap text-right">
										<span data-valor="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?=  ($producto['factura_v'] == 1) ? number_format( ($producto['costo']*0.87),2 ,'.','') : number_format( ($producto['costo']),2 ,'.','') ; ?></span>
										<span class="hidden" data-valorsi="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= $producto['costo'] ; ?></span>
										<span class="hidden" data-factura_v="<?= $producto['id_producto'] .'_'. $producto['id_detalle']; ?>"><?= $producto['factura_v'] ; ?></span>
									</td>
									<td class="text-nowrap">
										<button type="button" class="btn btn-xs btn-primary" data-egresar="<?= $producto['id_producto'].'_'. $producto['id_detalle']; ?>" data-toggle="tooltip" data-title="Egresar"><span class="glyphicon glyphicon-share-alt"></span></button>
										<button type="button" class="btn btn-xs btn-success" data-actualizar="<?= $producto['id_producto'] .'_'. $producto['id_detalle'] . '|' . $almacen['id_almacen']; ?>" data-toggle="tooltip" data-title="Actualizar stock y precio del producto"><span class="glyphicon glyphicon-refresh"></span></button>
									</td>
								</tr>
							<?php } //del if 
							} // del foreach ?>
						</tbody>
					</table>
				<?php } else { ?>
					<div class="alert alert-danger">
						<strong>Advertencia!</strong>
						<p>No existen productos registrados en la base de datos.</p>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
<h2 class="btn-info position-left-bottom display-table btn-circle margin-all display-table" data-toggle="tooltip" data-title="Esto es un egreso" data-placement="right"><i class="glyphicon glyphicon-log-out display-cell"></i></h2>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/jquery.dataTables.min.js"></script>
<script src="<?= js; ?>/dataTables.bootstrap.min.js"></script>
<script src="<?= js; ?>/selectize.min.js"></script>
<script src="<?= js; ?>/bootstrap-notify.min.js"></script>
<script>
	$(function() {
		var table;

		$('[data-egresar]').on('click', function() {
			adicionar_producto($.trim($(this).attr('data-egresar')));
		});

		$('[data-actualizar]').on('click', function() {
			var actualizar = $.trim($(this).attr('data-actualizar'));
			actualizar = actualizar.split('|');
			var id_producto = actualizar[0];
			var id_almacen = actualizar[1];

			$('#loader').fadeIn(100);

			$.ajax({
				type: 'post',
				dataType: 'json',
				url: '?/egresos/actualizar',
				data: {
					id_producto: id_producto,
					id_almacen: id_almacen
				}
			}).done(function(producto) {
				if (producto) {
					var precio = parseFloat(producto.precio).toFixed(2);
					var stock = parseInt(producto.stock);
					var cell;

					cell = table.cell($('[data-valor=' + producto.id_producto + ']'));
					cell.data(precio);
					cell = table.cell($('[data-stock=' + producto.id_producto + ']'));
					cell.data(stock);
					table.draw();

					var $producto = $('[data-producto=' + producto.id_producto + ']');
					var $cantidad = $producto.find('[data-cantidad]');
					var $precio = $producto.find('[data-precio]');

					if ($producto.size()) {
						$cantidad.attr('data-validation-allowing', 'range[1;' + stock + ']');
						$cantidad.attr('data-validation-error-msg', 'Debe ser un número positivo entre 1 y ' + stock);
						$precio.val(precio);
						$precio.attr('data-precio', precio);
						calcular_importe(producto.id_producto);
					}

					$.notify({
						title: '<strong>Actualización satisfactoria!</strong>',
						message: '<div>El stock y el precio del producto se actualizaron correctamente.</div>'
					}, {
						type: 'success'
					});
				} else {
					$.notify({
						title: '<strong>Advertencia!</strong>',
						message: '<div>Ocurrió un problema, no existe almacén principal.</div>'
					}, {
						type: 'danger'
					});
				}
			}).fail(function() {
				$.notify({
					title: '<strong>Advertencia!</strong>',
					message: '<div>Ocurrió un problema y no se pudo actualizar el stock ni el precio del producto.</div>'
				}, {
					type: 'danger'
				});
			}).always(function() {
				$('#loader').fadeOut(100);
			});
		});

		table = $('#productos').DataTable({
			info: false,
			lengthMenu: [
				[25, 50, 100, 500, -1],
				[25, 50, 100, 500, 'Todos']
			],
			order: []
		});

		$('#productos_wrapper .dataTables_paginate').parent().attr('class', 'col-sm-12 text-right');

		$.validate({
			form: '#formulario',
			modules: 'basic'
		});

		$('#formulario').on('reset', function() {
			$('#ventas tbody').empty();
			calcular_total();
		});
	});

	function ca(obj, x) {
		var aa = obj[obj.selectedIndex].value;
		if (aa == "Traspaso") {
			$('#alma').show();
		} else {
			$('#alma').hide();
		}
	}

	function adicionar_producto(id_producto) {
		var $ventas = $('#ventas tbody');
		var $producto = $ventas.find('[data-producto=' + id_producto + ']');
		var $cantidad = $producto.find('[data-cantidad]');
		var numero = $ventas.find('[data-producto]').size() + 1;
		var codigo = $.trim($('[data-codigo=' + id_producto + ']').text());

        var nombre = $.trim($('[data-nombre=' + id_producto + ']').text());
        var lote = $.trim($('[data-lote=' + id_producto + ']').text());
        var vencimiento = $.trim($('[data-vencimiento=' + id_producto + ']').text());

        var cantidad2 = $.trim($('[data-cant=' + id_producto + ']').text());
		var stock = $.trim($('[data-stock=' + id_producto + ']').text());
		var valor = $.trim($('[data-valor=' + id_producto + ']').text());
		var valorsi = $.trim($('[data-valorsi=' + id_producto + ']').text());
		var factura_v = $.trim($('[data-factura_v=' + id_producto + ']').text());
		var plantilla = '';
		var cantidad;

        V_producto_simple=id_producto.split("_");
        id_producto_simple=V_producto_simple[0];

		if ($producto.size()) {
			cantidad = $.trim($cantidad.val());
			cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
			cantidad = (cantidad < 9999999) ? cantidad + 1 : cantidad;
			$cantidad.val(cantidad).trigger('blur');
		} else {
            plantilla = '<tr class="active" data-producto="' + id_producto + '">' +
            '<td class="text-nowrap text-middle"><b>' + numero + '</b></td>' +
            '<td class="text-nowrap text-middle"><input type="text" value="' + id_producto_simple + '" name="productos[]" class="translate" tabindex="-1" data-validation="required number" data-validation-error-msg="Debe ser número">' + codigo + '</td>' +
            '<td class="text-middle">'+nombre+'<br>'+lote+'<br>'+vencimiento+'';

            plantilla += '<input type="hidden" value=\'' + nombre + '\' name="nombres[]" class="form-control" data-validation="required">';
            plantilla += '<input type="hidden" value=\'' + lote + '\' name="lote[]" class="form-control" data-validation="required">';
            plantilla += '<input type="hidden" value=\'' + vencimiento + '\' name="vencimiento[]" class="form-control" data-validation="required">';
            // console.log(typeof id_producto);
			plantilla = plantilla +	'<td><input type="text" value="1" name="cantidades[]" class="form-control input-xs text-right" maxlength="7" autocomplete="off" data-cantidad="" data-validation="required number" data-validation-allowing="range[1;' + stock + ']" data-validation-error-msg="Debe ser un número positivo entre 1 y ' + stock + '" onkeyup="calcular_importe(\'' + id_producto + '\')"></td>' +
			'<td><input type="text" value="' + valor + '" name="precios[]" class="form-control input-xs text-right" autocomplete="off" data-precio="' + valor + '" data-validation="required number" readonly data-validation-allowing="range[0.01;10000000.00],float" data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(' + id_producto + ')"> ' +
				'<input type="text" value="' + valorsi + '" name="preciossi[]" class="form-control input-xs text-right hidden" autocomplete="off" data-preciosi="' + valorsi + '" data-validation="required number" readonly data-validation-allowing="range[0.01;10000000.00],float" data-validation-error-msg="Debe ser un número decimal positivo" onkeyup="calcular_importe(' + id_producto + ')"> '+
				'<input type="text" value="' + factura_v + '" name="factura_v[]" class="form-control input-xs text-right hidden" autocomplete="off" data-factura_v="' + factura_v + '" data-validation="required number" readonly data-validation-allowing="range[0.01;10000000.00],float" data-validation-error-msg="Debe ser un número decimal positivo: 1 o 0"> </td>' +
				'<td class="text-nowrap text-right" ><span data-importe="">0.00</span> <span class="hidden" data-importesi="">0.00</span></td>' +
				'<td class="text-nowrap text-center">';
				// console.log(id_producto.replace('_','%'));
				plantilla += '<button type="button" class="btn btn-xs btn-danger" data-toggle="tooltip" data-title="Eliminar producto" tabindex="-1" onclick="eliminar_producto(this, ' + id_producto + ')"><span class="glyphicon glyphicon-remove"></span></button>' +
				'</td>' +
				'</tr>';

			$ventas.append(plantilla);

			$ventas.find('[data-cantidad], [data-precio]').on('click', function() {
				$(this).select();
			});

			$ventas.find('[title]').tooltip({
				container: 'body',
				trigger: 'hover'
			});

			$.validate({
				form: '#formulario',
				modules: 'basic'
			});
		}

		calcular_importe(id_producto);
	}
	
	function eliminar_producto(elemento, id_producto) {
	    console.log(id_producto)
		bootbox.confirm('Está seguro que desea eliminar el producto?', function(result) {
			if (result) {
			    elemento.parentNode.parentNode.parentNode.removeChild(elemento.parentNode.parentNode);
				//$('[data-producto=' + id_producto + ']').remove();
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

    function calcular_importe(id_producto) {
    	var $ventas = $('#ventas tbody');
    	var $producto = $ventas.find('[data-producto=' + id_producto + ']');
    	var $cantidad = $producto.find('[data-cantidad]');
    	var $precio = $producto.find('[data-precio]');
		var $preciosi = $producto.find('[data-preciosi]');
    	var $importe = $producto.find('[data-importe]');
		var $importesi = $producto.find('[data-importesi]');
    	var cantidad, precio, importe;

    	cantidad = $.trim($cantidad.val());
    	cantidad = ($.isNumeric(cantidad)) ? parseInt(cantidad) : 0;
    	precio = $.trim($precio.val());
    	precio = ($.isNumeric(precio)) ? parseFloat(precio) : 0.00;

		preciosi = $.trim($preciosi.val());
		preciosi = ($.isNumeric(preciosi)) ? parseFloat(preciosi) : 0.00;


    	importe = cantidad * precio;
    	importe = importe.toFixed(2);

		importesi = cantidad * preciosi;
		importesi = importesi.toFixed(2);


    	$importe.text(importe);

		$importesi.text(importesi);

    	calcular_total();
    }


	function calcular_total() {
		var $ventas = $('#ventas tbody');
		var $total = $('[data-subtotal]:first');
		var $totalsi = $('[data-subtotalsi]:first');
		var $importes = $ventas.find('[data-importe]');
		var $importessi = $ventas.find('[data-importesi]');
		var importe, importesi, total = 0, totalsi = 0;

		$importes.each(function(i) {
			importe = $.trim($(this).text());
			importe = parseFloat(importe);
			total = total + importe;
		});
		$importessi.each(function(i) {
			importesi = $.trim($(this).text());
			importesi = parseFloat(importesi);
			totalsi = totalsi + importesi;
		});

		$total.text(total.toFixed(2));
		$totalsi.text(totalsi.toFixed(2));
		$('[data-ventas]:first').val($importes.size()).trigger('blur');
		$('[data-total]:first').val(total.toFixed(2)).trigger('blur');

		$('[data-ventas]:first').val($importessi.size()).trigger('blur');
		$('[data-totalsi]').val(totalsi.toFixed(2)).trigger('blur');
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
	
	function listar_visitadores(seleccionado){
	    console.log(seleccionado.value);
        seleccionado = seleccionado.value;
        
        if(seleccionado =='Egreso especial'){
            $('#visitadores_select').css({'display':'block'});
            
    	} else{
        	$('#visitadores_select').css({'display':'none'});
        	
    	}
    }
</script>

<?php require_once show_template('footer-empty'); ?>