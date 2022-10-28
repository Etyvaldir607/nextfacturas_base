<?php

// Obtiene el almacen principal
$almacen = $db->from('inv_almacenes')->where('principal', 'S')->fetch_first();
$id_almacen = ($almacen) ? $almacen['id_almacen'] : 0;

// Obtiene los productos
$productos = $db->query("select p.id_producto, p.imagen, substr(p.codigo, 3) as codigo, p.nombre, p.nombre_factura, p.cantidad_minima, p.precio_actual, ifnull(e.cantidad_ingresos, 0) as cantidad_ingresos, ifnull(s.cantidad_egresos, 0) as cantidad_egresos, u.unidad, u.sigla, c.categoria, p.categoria_id, p.unidad_id from inv_productos p left join (select d.producto_id, sum(d.cantidad) as cantidad_ingresos from inv_ingresos_detalles d left join inv_ingresos i on i.id_ingreso = d.ingreso_id where i.almacen_id = $id_almacen group by d.producto_id) as e on e.producto_id = p.id_producto left join (select d.producto_id, sum(d.cantidad) as cantidad_egresos from inv_egresos_detalles d left join inv_egresos e on e.id_egreso = d.egreso_id where e.almacen_id = $id_almacen group by d.producto_id) as s on s.producto_id = p.id_producto left join inv_unidades u on u.id_unidad = p.unidad_id left join inv_categorias c on c.id_categoria = p.categoria_id")->fetch();

// Obtiene el modelo unidades
$unidades = $db->from('inv_unidades')->order_by('unidad')->fetch();

// Obtiene el modelo categorias
$categorias = $db->from('inv_categorias')->order_by('categoria')->fetch();

?>
<?php require_once show_template('header-advanced'); ?>
<div class="panel-heading">
	<h3 class="panel-title">
		<span class="glyphicon glyphicon-option-vertical"></span>
		<strong>Registrar producto y stock</strong>
	</h3>
</div>
<div id="productos" class="hidden">
	<?php foreach ($productos as $producto) { ?>
	<div id="producto_<?= escape($producto['id_producto']); ?>" data-codigo="<?= escape($producto['codigo']); ?>" data-nombre="<?= escape($producto['nombre']); ?>" data-categoria="<?= escape($producto['categoria_id']); ?>" data-unidad="<?= escape($producto['unidad_id']); ?>" data-precio="<?= escape($producto['precio_actual']); ?>"></div>
	<?php } ?>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-sm-8 hidden-xs">
			<div class="text-label text-danger">En este apartado podrá crear el producto y registrar la compra de manera enlazada:</div>
		</div>
		<div class="col-xs-12 col-sm-4 text-right">
			<a href="?/registros/crear" class="btn btn-primary"><i class="glyphicon glyphicon-refresh"></i><span> Actualizar</span></a>
		</div>
	</div>
	<hr>
	<div class="row">
		<div class="col-sm-8 col-sm-offset-2">
			<form method="post" action="?/registros/guardar" class="form-horizontal" autocomplete="off">
				<h3>Búsqueda</h3>
				<div class="form-group">
					<label for="busqueda" class="col-md-3 control-label">Buscar:</label>
					<div class="col-md-9">
						<select name="busqueda" id="busqueda" class="form-control" data-validation="alphanumeric length" data-validation-allowing="-/.#º() " data-validation-length="max20" data-validation-optional="true">
							<option value="">Buscar</option>
							<?php foreach ($productos as $producto) { ?>
							<option value="<?= escape($producto['id_producto']); ?>" data-nombre="<?= escape($producto['nombre']); ?>"><?= escape($producto['codigo']); ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<hr>
				<h3>Datos del producto</h3>
				<div class="form-group">
					<label for="codigo" class="col-md-3 control-label">Código:</label>
					<div class="col-md-9">
						<input type="text" value="" name="codigo" id="codigo" class="form-control" data-validation="required alphanumeric length" data-validation-allowing="-/.#º() " data-validation-length="max20">
					</div>
				</div>
				<div class="form-group">
					<label for="nombre" class="col-md-3 control-label">Nombre:</label>
					<div class="col-md-9">
						<input type="text" value="" name="nombre" id="nombre" class="form-control" data-validation="required letternumber length" data-validation-allowing="+-/.#º'() " data-validation-length="max100">
					</div>
				</div>
				<div class="form-group">
					<label for="categoria_id" class="col-md-3 control-label">Categoría:</label>
					<div class="col-md-9">
						<select name="categoria_id" id="categoria_id" class="form-control" data-validation="required">
							<option value="">Seleccionar</option>
							<?php foreach ($categorias as $elemento) { ?>
							<option value="<?= $elemento['id_categoria']; ?>"><?= escape($elemento['categoria']); ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<div class="form-group">
					<label for="unidad_id" class="col-md-3 control-label">Unidad:</label>
					<div class="col-md-9">
						<select name="unidad_id" id="unidad_id" class="form-control" data-validation="required">
							<option value="">Seleccionar</option>
							<?php foreach ($unidades as $elemento) { ?>
							<option value="<?= $elemento['id_unidad']; ?>"><?= escape($elemento['unidad']); ?></option>
							<?php } ?>
						</select>
					</div>
				</div>
				<hr>
				<h3>Datos del ingreso</h3>
				<div class="form-group">
					<label for="precio" class="col-md-3 control-label">Precio:</label>
					<div class="col-md-9">
						<input type="text" value="" name="precio" id="precio" class="form-control" data-validation="required number" data-validation-allowing="float">
					</div>
				</div>
				<div class="form-group">
					<label for="costo" class="col-md-3 control-label">Costo:</label>
					<div class="col-md-9">
						<input type="text" value="" name="costo" id="costo" class="form-control" data-validation="required number" data-validation-allowing="float">
					</div>
				</div>
				<div class="form-group">
					<label for="cantidad" class="col-md-3 control-label">Cantidad:</label>
					<div class="col-md-9">
						<input type="text" value="" name="cantidad" id="cantidad" class="form-control" data-validation="required number" data-validation-allowing="float">
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-9 col-md-offset-3">
						<button type="submit" class="btn btn-primary">
							<span class="glyphicon glyphicon-floppy-disk"></span>
							<span>Guardar</span>
						</button>
						<button type="button" class="btn btn-default" onclick="javascript:window.location.reload();">
							<span class="glyphicon glyphicon-refresh"></span>
							<span>Restablecer</span>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script src="<?= js; ?>/jquery.form-validator.min.js"></script>
<script src="<?= js; ?>/jquery.form-validator.es.js"></script>
<script src="<?= js; ?>/selectize.min.js"></script>
<script>
$(function () {
	var $busqueda = $('#busqueda');
	var $codigo = $('#codigo');
	var $nombre = $('#nombre');
	var $categoria_id = $('#categoria_id');
	var $unidad_id = $('#unidad_id');
	var $precio = $('#precio');
	var $costo = $('#costo');
	var $cantidad = $('#cantidad');

	$.validate({
		modules: 'basic'
	});

	$busqueda.selectize({
		persist: false,
		createOnBlur: true,
		create: true,
		onInitialize: function () {
			$busqueda.css({
				display: 'block',
				left: '-10000px',
				opacity: '0',
				position: 'absolute',
				top: '-10000px'
			});
		},
		onChange: function () {
			$busqueda.trigger('blur');
		},
		onBlur: function () {
			$busqueda.trigger('blur');
		}
	}).on('change', function (e) {
		var id_producto = $(this).val();
		var $producto = $('#producto_' + id_producto);
		var codigo = $producto.attr('data-codigo');
		var nombre = $producto.attr('data-nombre');
		var categoria = $producto.attr('data-categoria');
		var unidad = $producto.attr('data-unidad');
		var precio = $producto.attr('data-precio');
		if (codigo) {
			$codigo.val(codigo);
			$codigo.trigger('blur');
			$nombre.val(nombre);
			$nombre.trigger('blur');
			$categoria_id.val(categoria);
			$categoria_id.trigger('blur');
			$unidad_id.val(unidad);
			$unidad_id.trigger('blur');
			$precio.val(precio);
			$precio.trigger('blur');
			$costo.val('');
			$costo.trigger('blur');
			$cantidad.val('');
			$cantidad.trigger('blur');
			$codigo.prop('disabled', true);
			$nombre.prop('disabled', true);
			$categoria_id.prop('disabled', true);
			$unidad_id.prop('disabled', true);
		} else {
			$codigo.val(id_producto);
			$codigo.trigger('blur');
			$nombre.val('');
			$nombre.trigger('blur');
			$categoria_id.val(null);
			$categoria_id.trigger('blur');
			$unidad_id.val(null);
			$unidad_id.trigger('blur');
			$precio.val('');
			$precio.trigger('blur');
			$costo.val('');
			$costo.trigger('blur');
			$cantidad.val('');
			$cantidad.trigger('blur');
			$codigo.prop('disabled', false);
			$nombre.prop('disabled', false);
			$categoria_id.prop('disabled', false);
			$unidad_id.prop('disabled', false);
			$nombre.focus();
		}
	});
});
</script>
<?php require_once show_template('footer-advanced'); ?>